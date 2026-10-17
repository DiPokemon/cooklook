#!/usr/bin/env node
'use strict';

// Validate a fresh local snapshot; this command does not access WordPress or change data.
// php tools/seo-cohort-snapshot.php > build/seo-cohort-inventory.json
// node tools/seo-cohort-check.cjs build/seo-cohort-inventory.json
const fs = require('node:fs');
const path = require('node:path');
const { createHash } = require('node:crypto');
const { isDeepStrictEqual } = require('node:util');

const digest = value => createHash('sha256').update(value).digest('hex');
const parse = value => JSON.parse(value.toString('utf8').replace(/^\uFEFF/, ''));
const frozenFields = [
  'id', 'url', 'title', 'published_gmt', 'modified_gmt', 'categories', 'ingredient_names',
  'ingredient_count', 'step_count', 'instruction_words', 'portions', 'time_present',
  'image_count', 'recipe_schema_present', 'excerpt_present', 'excerpt_sha256',
  'source_sha256', 'recipe_body_sha256', 'matching_features_sha256',
  'image_urls_sha256', 'public_editorial_sha256',
];
const grains = { millet: /пшено|пшенн/, semolina: /манн/, corn: /кукуруз/, buckwheat: /греч/, oat: /овес|овсян|геркулес/ };

function validateCohort(cohort, inventory, batchBytes) {
  const errors = [];
  const check = (condition, message) => { if (!condition) errors.push(message); };
  const batch = parse(batchBytes);
  const entries = new Map(inventory.recipes.map(recipe => [recipe.id, recipe]));
  const batchIds = batch.recipes.map(recipe => recipe.id);
  const treatmentIds = cohort.pairs.map(pair => pair.treatment.id);
  const controlIds = cohort.pairs.map(pair => pair.control.id);
  const excludedIds = cohort.excluded_treatment.map(recipe => recipe.id);
  const unique = values => new Set(values).size === values.length;
  const rules = cohort.selection.rules;

  check(inventory.site === 'http://cooklook/' && cohort.site === inventory.site && batch.site === inventory.site, 'Local site mismatch.');
  check(inventory.database_writes === false && inventory.search_metrics === null, 'Read-only snapshot required.');
  check(digest(batchBytes) === cohort.editorial_batch_sha256, 'Editorial batch changed.');
  check(unique(inventory.recipes.map(recipe => recipe.id)), 'Duplicate inventory IDs.');
  check(unique(inventory.recipes.map(recipe => recipe.url)), 'Duplicate inventory URLs.');
  check(unique(batchIds), 'Duplicate editorial IDs.');
  check(unique(cohort.pairs.map(pair => pair.pair_id)), 'Duplicate pair IDs.');
  check(cohort.pairs.length > 0 && unique(treatmentIds) && unique(controlIds), 'Empty cohort or reused pair members.');
  check(unique([...treatmentIds, ...excludedIds]), 'Repeated treatment or exclusion.');
  check(isDeepStrictEqual([...treatmentIds, ...excludedIds].sort((a, b) => a - b), [...batchIds].sort((a, b) => a - b)), 'Pairs and exclusions must cover the editorial batch exactly.');
  check(controlIds.every(id => !batchIds.includes(id)), 'A control has received the editorial intervention.');
  for (const flag of ['same_grain_family', 'same_top_level_category', 'recipe_schema_and_image_required', 'empty_control_excerpt', 'no_identical_recipe_body']) {
    check(rules[flag] === true, `Selection rule disabled: ${flag}.`);
  }
  for (const key of ['max_ingredient_count_difference', 'max_step_count_difference', 'max_instruction_word_ratio', 'min_instruction_words', 'max_publication_gap_days']) {
    check(Number.isFinite(rules[key]) && rules[key] > 0, `Invalid selection threshold: ${key}.`);
  }

  // Check all ten edited recipes, including those excluded from the comparison.
  for (const edited of batch.recipes) {
    const current = entries.get(edited.id);
    check(Boolean(current), `Missing edited recipe ${edited.id}.`);
    if (!current) continue;
    check(current.excerpt_sha256 === digest(edited.excerpt), `Edited excerpt changed: ${edited.id}.`);
    check(current.source_sha256 === digest(JSON.stringify(edited.source)), `Edited source facts changed: ${edited.id}.`);
  }

  const pairs = cohort.pairs.map(pair => {
    const before = errors.length;
    const treatment = entries.get(pair.treatment.id);
    const control = entries.get(pair.control.id);
    for (const frozen of [pair.treatment, pair.control]) {
      const current = entries.get(frozen.id);
      check(Boolean(current), `Missing pair member ${frozen.id}.`);
      if (!current) continue;
      for (const field of frozenFields) {
        check(Object.hasOwn(frozen, field) && Object.hasOwn(current, field) && isDeepStrictEqual(current[field], frozen[field]), `Recipe ${frozen.id} changed: ${field}.`);
      }
      check(current.recipe_schema_present && current.image_count > 0, `Recipe/image required: ${frozen.id}.`);
      const names = current.ingredient_names.join(' ').toLowerCase().replace(/ё/g, 'е');
      check(Boolean(grains[pair.grain_family]?.test(names)), `Grain family mismatch: ${frozen.id}.`);
    }
    if (!treatment || !control) return { pair_id: pair.pair_id, passed: false };
    check(control.excerpt_present === false && control.excerpt_sha256 === digest(''), `Control excerpt is no longer empty: ${control.id}.`);
    check(treatment.recipe_body_sha256 !== control.recipe_body_sha256, `Identical recipe bodies: ${pair.pair_id}.`);
    const roots = treatment.categories.filter(category => category.parent === 0).map(category => category.id);
    check(control.categories.some(category => category.parent === 0 && roots.includes(category.id)), `Top-level category mismatch: ${pair.pair_id}.`);
    const deltas = {
      ingredients: Math.abs(treatment.ingredient_count - control.ingredient_count),
      steps: Math.abs(treatment.step_count - control.step_count),
      instruction_word_ratio: Math.max(treatment.instruction_words, control.instruction_words) / Math.min(treatment.instruction_words, control.instruction_words),
      publication_gap_days: Math.abs(Date.parse(treatment.published_gmt.replace(' ', 'T') + 'Z') - Date.parse(control.published_gmt.replace(' ', 'T') + 'Z')) / 86400000,
    };
    check(deltas.ingredients <= rules.max_ingredient_count_difference, `Ingredient count gap: ${pair.pair_id}.`);
    check(deltas.steps <= rules.max_step_count_difference, `Step count gap: ${pair.pair_id}.`);
    check(deltas.instruction_word_ratio <= rules.max_instruction_word_ratio && Math.min(treatment.instruction_words, control.instruction_words) >= rules.min_instruction_words, `Instruction length gap: ${pair.pair_id}.`);
    check(Number.isFinite(deltas.publication_gap_days) && deltas.publication_gap_days <= rules.max_publication_gap_days, `Publication date gap: ${pair.pair_id}.`);
    check(Boolean(pair.reason && pair.differences), `Document matching reasons and limitations: ${pair.pair_id}.`);
    return { pair_id: pair.pair_id, treatment_id: treatment.id, control_id: control.id, passed: errors.length === before, deltas };
  });
  return {
    checked_at: new Date().toISOString(),
    passed: errors.length === 0,
    inventory_generated_at: inventory.generated_at,
    site: inventory.site,
    public_recipes: inventory.recipes.length,
    paired_recipes: cohort.pairs.length * 2,
    excluded_treatment_ids: excludedIds,
    environment_changes: Object.keys(cohort.environment).filter(key => cohort.environment[key] !== inventory[key]),
    database_writes: false,
    search_metrics_available: false,
    interpretation: 'Validates the recorded local selection and unchanged content, not equal search demand or an SEO effect. Environment changes are reported separately.',
    pairs,
    errors,
  };
}

if (require.main === module) {
  try {
    if (process.argv.length !== 3) throw new Error('Usage: node tools/seo-cohort-check.cjs <fresh-inventory.json>');
    const root = path.resolve(__dirname, '..');
    const inventoryBytes = fs.readFileSync(path.resolve(process.argv[2]));
    const cohortBytes = fs.readFileSync(path.join(root, 'docs/seo-editorial-cohort.json'));
    const result = validateCohort(parse(cohortBytes), parse(inventoryBytes), fs.readFileSync(path.join(root, 'docs/seo-editorial-batch.json')));
    result.inventory_sha256 = digest(inventoryBytes);
    result.cohort_sha256 = digest(cohortBytes);
    process.stdout.write(JSON.stringify(result, null, 2) + '\n');
    process.exitCode = result.passed ? 0 : 1;
  } catch (error) {
    process.stderr.write(error.message + '\n');
    process.exitCode = 1;
  }
}

module.exports = { validateCohort };
