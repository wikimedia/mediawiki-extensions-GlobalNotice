<?php
/**
 * GlobalNotice -- global (undismissable) sitenotice for wiki farms
 *
 * @file
 * @ingroup Extensions
 * @version 0.4
 * @author Misza <misza@shoutwiki.com>
 * @author Jack Phoenix <jack@shoutwiki.com>
 * @copyright Copyright © 2010 Misza
 * @copyright Copyright © 2010-2015 Jack Phoenix
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @link https://www.mediawiki.org/wiki/Extension:GlobalNotice Documentation
 */

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'GlobalNotice',
	'version' => '0.4',
	'author' => array( 'Misza', 'Jack Phoenix' ),
	'descriptionmsg' => 'globalnotice-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:GlobalNotice',
	'license-name' => 'GPL-2.0+',
);

$wgMessagesDirs['GlobalNotice'] = __DIR__ . '/i18n';

$wgAutoloadClasses['GlobalNotice'] = __DIR__ . '/GlobalNotice.class.php';

$wgHooks['SiteNoticeAfter'][] = 'GlobalNotice::onSiteNoticeAfter';
//$wgHooks['EditPage::showEditForm:initial'][] = 'GlobalNotice::displayNoticeOnEditPage';