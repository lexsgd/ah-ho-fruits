<?php
/**
 * The base configuration for WordPress
 *
 * @package WordPress
 */

// ** Database settings ** //
define( 'DB_NAME', 'contactl_wp153' );
define( 'DB_USER', 'contactl_wp153' );
define( 'DB_PASSWORD', 'sf[dMzM,y7@I' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 */
define('AUTH_KEY',         '>}-:@^!*p?oBo6az k3!8bVN8E-pupv/pr/$FB5w]t/r!z]z)vPX$j+?i*]L?,Yr');
define('SECURE_AUTH_KEY',  ') yz)]R9jz:9cED:Ya|48*kZP_(PBpL`2[~ef`&8<7$U{<X>hdx-{n[sWT%glWRK');
define('LOGGED_IN_KEY',    'lj-T{!W_$3k6Q*IS4SV)>d4dO$|pbaWc@:0+1TS:]A5kq+PfgZ3Q{2G6A>lM7i[+');
define('NONCE_KEY',        '~*6@^Nzx2WV9>j0l6`_{d 8,G.Cq)2/=ekc3$Xg-6UJ[r(8L&%&+#DQJf|4WY_)*');
define('AUTH_SALT',        '/tgSZH]f|-/C-lRC>t|f)w@h~QNqg^88|c^6.n:CpwiP(z+RPCh}ceDc,GBH=+= ');
define('SECURE_AUTH_SALT', ',>e9cUC0I&ZN|oop%u|HOh[v(2Gu0FD+k5,NjlE&i0/EXf8.RmkVJ}U4Pp^cC-KX');
define('LOGGED_IN_SALT',   '-b^m<EUn67+<DwpL<tyrT0]hP4x,}r3T@q}yN;SZA+mTg;m_TSna<O4]xp+pf r+');
define('NONCE_SALT',       '|.JA~N_hLQ:%0ckpOIRBQ2@!|#GDNjSv^[=!Q1iKGhfIYErHM[jjM1?BoM/ia>kL');
/**#@-*/

$table_prefix = 'wpgr_';

define( 'WP_DEBUG', false );

/* Add any custom values here */
define( 'WP_HOME', 'https://fruits.heymag.app' );
define( 'WP_SITEURL', 'https://fruits.heymag.app' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
