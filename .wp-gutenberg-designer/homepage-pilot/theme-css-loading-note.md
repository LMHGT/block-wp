# Theme CSS Loading Note

WordPress Playground did not enqueue the block theme style.css on the front end. Runtime CSS was mirrored into theme.json under styles.css so WordPress emits it through global-styles-inline-css without adding PHP enqueue code.
