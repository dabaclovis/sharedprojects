# Application areas

- `app/Livewire/Admins` and `/admins/*`: active administrators manage accounts, articles, products, events, paid audits, and sponsorships across all owners. The overview includes quote management and the contact inbox. Content review supports publishing, archiving/cancelling, trash, restoration, and private article remarks. Reviews detect concurrent content changes before moderation.
- `app/Livewire/Users` and `/users/*`: active members, including administrators, create and manage their own articles, affiliate products, calendar, profile, and settings. Queries and mutations remain scoped to the signed-in owner.
- `app/Livewire/Pages` and `/pages/*`: everyone can browse public content, contact the business, request services, and use public tools. `/` also serves the public homepage. Reports retain their signed-link or session privacy controls.

Navigation follows the current area. Administrators can enter their personal workspace using the account menu; visiting public pages always shows public navigation.

Old `/admin/*` and `/services/*` bookmarks redirect to their new destinations. Previously signed report URLs stay available at their original paths so existing signatures remain valid. Deprecated `Services` component wrappers preserve compatibility; new code should use the canonical namespaces and route names.

Restoring articles and products returns them to draft. Restoring events leaves them cancelled. Administrative restoration never automatically republishes content or schedules an event.

After deployment, clear stale route and compiled-view caches (`php artisan route:clear` and `php artisan view:clear`) and rebuild them using the normal deployment process. This organization change requires no additional database migration.
