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
define( 'DB_NAME', 'woostore' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define('AUTH_KEY',         '8<433yq|PM&vUVf/nP92jY|HIB22K:NW;(YI}tNX95DPQP~K-t-<J;ji%jJ-svrM');
define('SECURE_AUTH_KEY',  '<HZ6,hXd1k{wtP-=er.v36%?h$FADRb|r)RA+O&=}PBrB9<7KS9jUWG1Z^w3f>+G');
define('LOGGED_IN_KEY',    '-=5qri,9BIcYtnl$+jCUg`+i]_F,F?aFw:WpC&+WkUL1u={e=QrZHS9-2|jUsc2j');
define('NONCE_KEY',        'W`m-ftt+TgDL}+?Ah2wW#ji)QE-hq4`;y| XJ2|PL/MDusN5Nl%YPe=GW,qu&1@-');
define('AUTH_SALT',        'OpiRnva{&-s>2o-;F+D:ojF*l!>,Fv&BAj)WIb{8B[S;4v;Ovw{;Mt_*:UZ&sN_a');
define('SECURE_AUTH_SALT', 'TEheq5Sw6.P3MuUflwmlo<sg(e()QU+<R%]J{nNRu}45k6O^t.k*pbiZHNMB[#l*');
define('LOGGED_IN_SALT',   'Sd=X0o!srF6oFl5qt1^w>1R>yOjYx;m^<E#Ev>UkZ0l&{`F:}^*[vUbD%0`=;ao.');
define('NONCE_SALT',       'tuj>}V+1J6u:PAz-US_TFD5_^1x~vd]$H4c5PM%Q.@IUY.U;ffCQKn]bw}&[rj{4');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
