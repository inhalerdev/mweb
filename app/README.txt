Mineacle V1.31 Approved Modal Integrated

This integrates the exact modal mockup design into the website.

Changed:
- app/bans.php modal markup replaced with approved modal structure
- app/assets/styles.css includes approved modal styling
- app/assets/main.js fills approved modal fields:
  modalAvatar
  modalName
  modalStatus
  modalTypeBadge
  modalReason
  modalDuration
  modalDate
  modalAppeal
  modalEmail
  modalDiscord
  modalActions
  modalNote
- Info buttons still use data-info-index and inline fallback onclick
- Modal z-index remains above all layers
- Cache bumped to foundation1.31

Install:
- Replace the live app/ folder fully
- Purge deployment/cache
