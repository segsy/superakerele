<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'superak1_wp826' );

/** Database username */
define( 'DB_USER', 'superak1_wp826' );

/** Database password */
define( 'DB_PASSWORD', '!S4X446pO!' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'r79j9kgafomwakzvax7odzkpib89zwhxd6gpmm3wfa3hhha40dlu6hapf6l1bldf' );
define( 'SECURE_AUTH_KEY',  'aw8jjjodgisuvp5ts0askampb1drnhft1z7vyuvjdvogl8aj4xhlwnzzy9bcsrr8' );
define( 'LOGGED_IN_KEY',    'pnkc6itmmixyuovzuof7suq4luexaxvhynlx1bzwgmn4dsqipeuvs2rpyanykrt0' );
define( 'NONCE_KEY',        'cfll0wfi0hu29aldzhtka3tzsa96osfluxom4tdh7mec6ebh873isqhjxrl6y7n0' );
define( 'AUTH_SALT',        '4vjkahqpejglxyvc4v7cbs4te30hrdz90izqg8hx92ouq2nrle0sgyxm6asfepfu' );
define( 'SECURE_AUTH_SALT', 'oeeifozdwre2g7kswhitfnz0r8su0nsqucplawjnswhdsndgqowutmjigwh9hcma' );
define( 'LOGGED_IN_SALT',   '9otldzzlnctu8tnuxzxodfxqkg0wcgwldwf95yk7t2mftcivcjnyfklkjvgpj39c' );
define( 'NONCE_SALT',       'lcy6yvyv79xws6e7pc996wxna8j76uu33kt7eg96tif2uzkk0mtxkvlgk3pbr9hy' );

/* Set memory limit to 256MB */
define( 'WP_MEMORY_LIMIT', '256M' );

/* Set PHP execution time to 60 seconds */
set_time_limit(60);

/* Set PHP max input vars to 2000 */
ini_set( 'max_input_vars', '2000' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wppa_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
