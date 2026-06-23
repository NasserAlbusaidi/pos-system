import './bootstrap';
import Sortable from 'sortablejs';

// Self-hosted (bundled) so it loads under our CSP `script-src 'self'`.
// menu-builder.blade.php drag-to-reorder uses `new Sortable(...)` via Alpine x-init.
window.Sortable = Sortable;
