<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'pet_site' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '@Q<V7Q~?(6E#D/5/Wx(bXYe<vw%y`]Sr94:An@0_T=cWA*#/~bVLT%p;~)u>@`Rj' );
define( 'SECURE_AUTH_KEY',  '0MO7BMTrtWl}Usn`Ii5ns]DwJ!upR7M+eBY;c%qrM;yp2k|`(XY3R(;/u?q|$~-B' );
define( 'LOGGED_IN_KEY',    '/rLT9acTDKSzb(1x]H(ixHK-7iKx^f_R(o^le`>b#k7^HA9t7XEzUuJR5*^&F$-K' );
define( 'NONCE_KEY',        '= 7/u!:UGY*xM#9qsKq$Tzd8J2/~fF]]bh7R(xkxeTun,/{4&.N1?YNEzARgo%BP' );
define( 'AUTH_SALT',        '}dk^(2fIafC_1u)CA4|JH}GpD}}*#dOp|QNem,e7%Ue  4aZ+u8I<SgP9F>S]bh6' );
define( 'SECURE_AUTH_SALT', '3{1W3yzY*e<Ui[]NmDj_,(K`B_}1?]sVt,1]9+)^$ Jg,oU*$#t>>MHeMDAE`rDw' );
define( 'LOGGED_IN_SALT',   'WF)6|$DisD/X*#yY:$(y}04cn=)v,dc?o>7.ddD$4*chlfooo6<RZ!X:|Ag6GKFw' );
define( 'NONCE_SALT',       'ck@3SjVPOg~dUh?,hIEG%$8c%1nazTvT)0^XC5)|[LuAa}.bDQA}|%K?|2n:)Jz+' );

/**#@-*/

/**
 * WordPress Database Table prefix.
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
