Mineacle LiteBans Website V5

This is a real PHP/MySQL LiteBans bans page.

Install:
1. Upload folder to a PHP web host with PDO MySQL enabled.
2. Copy includes/config.example.php to includes/config.php.
3. Edit includes/config.php with LiteBans MySQL details.
4. Use a READ-ONLY MySQL user.
5. Visit bans.php.

Recommended MySQL permissions:
CREATE USER 'mineacle_bans_readonly'@'WEB_SERVER_IP' IDENTIFIED BY 'strong-password-here';
GRANT SELECT ON litebans.* TO 'mineacle_bans_readonly'@'WEB_SERVER_IP';
FLUSH PRIVILEGES;

Expected tables: litebans_bans and litebans_history.
Expected bans columns: id, uuid, ip, reason, banned_by_name, time, until, active, ipban.
Expected history columns: uuid, name, date.

If your LiteBans schema differs, edit includes/config.php.

Payment note: Pay-to-unban links go to site.unban_checkout_url. This page does not automatically unban after payment. That requires a verified payment webhook.

IP ban rule: ipban=1 rows show as Permanently Banned with no payment/dispute option.

Security: never expose includes/config.php, use HTTPS, use read-only DB credentials, and do not display player IPs publicly.
