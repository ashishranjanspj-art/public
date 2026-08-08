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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

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
define( 'AUTH_KEY',          'lyyEfopADrwz}{d`!t(+Tv_,/yp)FpuxV{z;e(6VSu,nYA+OM o)h:DJzPOe))G4' );
define( 'SECURE_AUTH_KEY',   '5 G}Xa%1?1)Onms3-(1{v}e&{<&}qW_>=VZN|4=,tS^va75}]u$0mYhjE8mi}9iO' );
define( 'LOGGED_IN_KEY',     'vG:8b!Y()t(/C*9n5CwJl^eEEZ<k$qyfN~XLA$;(BQ5C!Ouq>Nc.-F&In;(kM,}0' );
define( 'NONCE_KEY',         '*vMkx*AfkC<M@R8ORh ,gQw2H<.hM+& ~i1~:i@fL&a}=oRN%TBAz7TN(se@CQrM' );
define( 'AUTH_SALT',         '2FcVFyIO9;9,d$a065E^jTlKk.OsQitdR%3.~?]wbAt,KAY|Fq]s%`rfsd,aEb{?' );
define( 'SECURE_AUTH_SALT',  't0$v;4;J971)jh4JyXnw*Y%EeF`GKbhO3P#i c4Q?cbL1q$QRRq.~e V.AlH%@gN' );
define( 'LOGGED_IN_SALT',    'O]Lc%0fQu~M1.$c)fGh9vZapsszsC1l+Tf}&HiIMO!1QH?3./OXjbUjsP)U(^><T' );
define( 'NONCE_SALT',        'kGyIw{{Z{)I(UwY:ULF;(gR?,4q x:~(I/#Z{r}$TDMtW82*G5qI<3&Ln4F[?qBJ' );
define( 'WP_CACHE_KEY_SALT', '`sf]r|d${fnq # VcY`qFKobqTCKL+Xxjge$7h2v_M>YX7fzZ9|A?oz{%RZ!m_%^' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}
define('AYUMENT_OPENAI_API_KEY', '');
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
