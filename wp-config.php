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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dokon' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

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
define( 'AUTH_KEY',         'osh^GP5v1$Gp8;Y0XYJ(8s<^Q*%Fl7<dlh5B50/0_b6M#e6XDEeN`k3|#nMGnhPK' );
define( 'SECURE_AUTH_KEY',  '=|gyHW_@tRpw,LqAk$<1]T/&`pcm#[3ahUr,U@+U)^T.q;g_egQ$gu=@6zzR%[`3' );
define( 'LOGGED_IN_KEY',    '+Qi-HXlgiFo-Jh^(^(@,=ZZts)tBjMeZ1e=l>R0I$xOk#)[g9mJVQ]oR{]to{[e}' );
define( 'NONCE_KEY',        '>}:K=&<@;5Pu6g/Q E3DA|Q>m38O_})Uu,oB4 3i&!q<U/v-aS}wvatUL5E(]~PI' );
define( 'AUTH_SALT',        'fDx0)tVCo2kQH&b>B^8:R_L_:1Z^-UUMWJ+JFp-r|&|PT)?<V=[V7j%!:9Q]xXlj' );
define( 'SECURE_AUTH_SALT', '79]9c}%(>o{}Xb:5LWVT}/oAC`|fA*A[>(9R%(!n&01NN[5t>y)r ~R(Inm(IfFy' );
define( 'LOGGED_IN_SALT',   'O]kE5vp3q&OjdD~J.%GEjz4U/37 +fu6[.9nqXq!46s{D{4Iy34~6T*YSaEal[G~' );
define( 'NONCE_SALT',       'NN^XD29*0XM;a]jo=9n_:,NND4B#NQ!M*>ul=R4,hu1Np>SVM>Q~0g5$Jq>bjj,c' );

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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
