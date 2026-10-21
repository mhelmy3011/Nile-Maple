import fs from 'node:fs';
let code = fs.readFileSync('app/PublicController.php', 'utf-8');
code = code.replace(
  /\$data\['contact'\] = true;/,
  "$data['contact'] = true;\n                $data['jsBundles'] = ['base', 'contact'];"
);
code = code.replace(
  /\$data\['post'\] = \$post;/,
  "$data['post'] = $post;\n                $data['jsBundles'] = ['base', 'post'];"
);
fs.writeFileSync('app/PublicController.php', code);
