const { spawnSync } = require('node:child_process');
const { readdirSync } = require('node:fs');
const { join } = require('node:path');
let count = 0;
for (const directory of ['static/js', 'js']) {
	for (const name of readdirSync(join(__dirname, '..', directory))) {
		if (!name.endsWith('.js') || name.endsWith('.min.js')) continue;
		const result = spawnSync(process.execPath, ['--check', join(__dirname, '..', directory, name)], { encoding: 'utf8' });
		if (result.status) { process.stderr.write(result.stderr); process.exit(result.status); }
		count++;
	}
}
console.log(count + ' JavaScript files passed syntax checks.');
