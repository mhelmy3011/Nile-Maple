import fs from 'node:fs';
let css = fs.readFileSync('assets/src/app.css', 'utf-8');
if (!css.includes('.topbar-wa')) {
  css = css.replace('.topbar-social{', '.topbar-wa{color:#fff;display:inline-flex;align-items:center;gap:6px;background:var(--green-700);padding:0 12px;border-radius:24px;height:28px;text-decoration:none;font-weight:500;margin-inline-start:auto}\n.topbar-wa:hover{background:var(--green-800)}\n.topbar-wa .i{color:#fff !important;width:16px;height:16px}\n.topbar-social{');
  fs.writeFileSync('assets/src/app.css', css);
}

let topbar = fs.readFileSync('app/templates/ui/topbar.php', 'utf-8');
topbar = topbar.replace(
  '<a class="topbar-social" href="<?= View::e(\\Nm\\Util::waLink($t(\'wa.prefill\'))) ?>" target="_blank" rel="noopener" aria-label="WhatsApp" data-event="click_whatsapp"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 14.9L2 22l5.3-1.4A10 10 0 1 0 12 2Zm5.4 14.2c-.2.6-1.3 1.2-1.8 1.2s-.9.1-2.9-.6a10 10 0 0 1-4.2-3.7 5 5 0 0 1-1-2.7A2.8 2.8 0 0 1 8.3 8c.3 0 .5 0 .7.1s.5.1.7.5.9 2.2 1 2.3a.6.6 0 0 1 0 .5 2 2 0 0 1-.3.5l-.5.5c-.2.2-.4.4-.2.8a11.3 11.3 0 0 0 2 2.5 9.7 9.7 0 0 0 2.9 1.8c.4.2.6.2.8-.1s1-1.1 1.2-1.5.5-.3.8-.2 2 1 2.3 1.1.5.3.6.4a2.6 2.6 0 0 1-.2 1.5Z" fill="currentColor"/></svg></a>',
  '<a class="topbar-wa" href="<?= View::e(\\Nm\\Util::waLink($t(\'wa.prefill\'))) ?>" target="_blank" rel="noopener" aria-label="WhatsApp" data-event="click_whatsapp"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 14.9L2 22l5.3-1.4A10 10 0 1 0 12 2Zm5.4 14.2c-.2.6-1.3 1.2-1.8 1.2s-.9.1-2.9-.6a10 10 0 0 1-4.2-3.7 5 5 0 0 1-1-2.7A2.8 2.8 0 0 1 8.3 8c.3 0 .5 0 .7.1s.5.1.7.5.9 2.2 1 2.3a.6.6 0 0 1 0 .5 2 2 0 0 1-.3.5l-.5.5c-.2.2-.4.4-.2.8a11.3 11.3 0 0 0 2 2.5 9.7 9.7 0 0 0 2.9 1.8c.4.2.6.2.8-.1s1-1.1 1.2-1.5.5-.3.8-.2 2 1 2.3 1.1.5.3.6.4a2.6 2.6 0 0 1-.2 1.5Z" fill="currentColor"/></svg><span dir="ltr">WhatsApp</span></a>'
);
fs.writeFileSync('app/templates/ui/topbar.php', topbar);
