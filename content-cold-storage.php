<?php
/**
 * Plugin Name:      Content Cold Storage
 * Plugin URI:       https://github.com/norcross/content-cold-storage
 * Description:      A process to archive old WP content and remove it from all public viewing.
 * Version:          0.0.1
 * Author:           Andrew Norcross
 * Author URI:       https://andrewnorcross.com/
 * Text Domain:      content-cold-storage
 * Domain Path:      /languages
 * License:          MIT
 * License URI:      https://opensource.org/licenses/MIT
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage;

// Call our CLI namespace.
use WP_CLI;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our plugin version.
define( __NAMESPACE__ . '\VERS', '0.0.1' );

// Set a few prefixes.
define( __NAMESPACE__ . '\ACTION_PREFIX', 'ccs_' );
define( __NAMESPACE__ . '\META_PREFIX', 'ccs_meta_' );
define( __NAMESPACE__ . '\NONCE_PREFIX', 'ccs_nonce_' );
define( __NAMESPACE__ . '\OPTION_PREFIX', 'ccs_setting_' );

// And load our files.
require_once __DIR__ . '/includes/admin/config.php';
require_once __DIR__ . '/includes/admin/menu-items.php';
require_once __DIR__ . '/includes/admin/notices.php';
require_once __DIR__ . '/includes/admin/process.php';

require_once __DIR__ . '/includes/structure/bulk-actions.php';
require_once __DIR__ . '/includes/structure/post-types.php';
require_once __DIR__ . '/includes/structure/row-actions.php';
