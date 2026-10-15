// Keep both checked-in CSS variants synchronized using the existing Sass dependency.
const fs = require('node:fs');
const path = require('node:path');
let sass;
try { sass = require('node-sass'); }
catch (error) {
	console.error('Install the existing npm development dependencies before compiling SCSS. See README.md.');
	process.exit(1);
}
const directory = path.join(__dirname, '../static/css');
function compile() {
	for (const name of fs.readdirSync(directory)) {
		if (!name.endsWith('.scss') || name.startsWith('_')) continue;
		for (const [suffix, style] of [['.css', 'expanded'], ['.min.css', 'compressed']]) {
			const result = sass.renderSync({ file: path.join(directory, name), outputStyle: style });
			fs.writeFileSync(path.join(directory, name.replace(/\.scss$/, suffix)), result.css);
		}
	}
}
try { compile(); } catch (error) { console.error(error.message); process.exit(1); }
if (process.argv.includes('--watch')) {
	let timer;
	fs.watch(directory, (event, name) => {
		if (!name || !name.endsWith('.scss')) return;
		clearTimeout(timer);
		timer = setTimeout(() => { try { compile(); } catch (error) { console.error(error.message); } }, 150);
	});
}
