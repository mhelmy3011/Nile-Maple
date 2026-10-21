import fs from 'node:fs';
let code = fs.readFileSync('app/PublicController.php', 'utf-8');
code = code.replace(
  /\$data\['categories'\] = Content::categories\(\$lang\);/,
  "$data['categories'] = Content::categories($lang);\n                $data['jsBundles'] = ['base', 'listing'];"
);
code = code.replace(
  /\$data\['cat'\] = \$c;/,
  "$data['cat'] = $c;\n                $data['jsBundles'] = ['base', 'listing'];"
);
fs.writeFileSync('app/PublicController.php', code);
