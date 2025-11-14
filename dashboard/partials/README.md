Partials in this folder

Files:
- _init.php: helper function `render_partial($name, $vars = [])` to include partials safely and pass variables.
- menu.php: menu renderer. Edit the $menuItems array to change navigation labels, links, and icons.
- sidebar.php: desktop + mobile sidebar. Uses `render_partial('menu', ['mobile' => ...])`.
- header.php: header area (mobile menu button, title, profile).
- footer.php: script includes and closing tags.

Usage:
- In page files (e.g. `index.php`) include the helper once:

    include_once __DIR__ . '/partials/_init.php';

  then call:

    render_partial('sidebar');
    render_partial('header');
    render_partial('footer');

Notes:
- `render_partial` extracts the $vars array into variables available inside the partial (EXTR_SKIP to avoid overwriting local variables).
- Keep partials simple and focused; avoid heavy logic in partials. For complex rendering, consider moving logic to a separate helper file.
