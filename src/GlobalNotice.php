<?php

use MediaWiki\Hook\SiteNoticeAfterHook;
use MediaWiki\User\UserGroupManager;

/**
 * GlobalNotice -- global (undismissable) sitenotice for wiki farms
 *
 * @file
 * @ingroup Extensions
 * @author Misza <misza@shoutwiki.com>
 * @author Jack Phoenix <jack@shoutwiki.com>
 * @copyright Copyright © 2010 Misza
 * @copyright Copyright © 2010-2020 Jack Phoenix
 * @license GPL-2.0-or-later
 * @link https://www.mediawiki.org/wiki/Extension:GlobalNotice Documentation
 */

class GlobalNotice implements SiteNoticeAfterHook {

	/**
	 * @var UserGroupManager
	 */
	private $userGroupManager;

	/**
	 * @param UserGroupManager $userGroupManager
	 */
	public function __construct( UserGroupManager $userGroupManager ) {
		$this->userGroupManager = $userGroupManager;
	}

	/**
	 * @param string &$siteNotice Existing site notice (if any) to manipulate or
	 * append to
	 * @param Skin $skin
	 * @return bool
	 */
	public function onSiteNoticeAfter( &$siteNotice, $skin ) {
		global $wgGlobalNoticeFile;
		// It is possible that there is a global notice (for example, for all
		// French-speaking users) *and* a forced global notice (for everyone,
		// informing them of planned server maintenance etc.)
		//
		// We append whatever we have to this variable and if right before
		// returning this variable is non-empty, we wrap the local site-notice in
		// a div with id="localSiteNotice" because users may want to hide global
		// notices (or forced global notices...that'd be quite dumb though)
		//
		// Come to think of it...<s>on ShoutWiki, the $siteNotice variable will never
		// be empty because SendToAFriend hooks into SiteNoticeAfter hook, too, and
		// appends its HTML to it.</s> not true anymore, we disabled that ext.
		// in 2014 or so
		$ourSiteNotice = '';

		// GlobalNotice from a file system file; for the rare cases when MessageCommons ext.
		// has been disabled but we still want to display a global notice; like during a major
		// MediaWiki upgrade, for example
		if ( $wgGlobalNoticeFile !== false && file_exists( $wgGlobalNoticeFile ) ) {
			$siteNotice .= '<div style="text-align: center;" id="forcedGlobalNotice">';
			$siteNotice .= $skin->getOutput()->parseInlineAsInterface( file_get_contents( $wgGlobalNoticeFile ) );
			$siteNotice .= '</div>';
			// This is a special case, perform no further processing because we don't
			// care about the rest if $wgGlobalNoticeFile is set.
			return true;
		}

		// "Forced" globalnotice -- a site-wide notice shown for *all* users,
		// no matter what their language is
		// Used only for things like server migration notices etc.
		$forcedNotice = $skin->msg( 'forced-globalnotice' )->inLanguage( 'en' );
		if ( !$forcedNotice->isDisabled() ) {
			$ourSiteNotice .= '<div style="text-align: center;" id="forcedGlobalNotice">' .
				$forcedNotice->parseAsBlock() . '</div>';
		}

		// Global notice, depending on the user's language
		// This can be used to show language-specific stuff to users with a certain
		// interface language (i.e. "We need more French translators! Pouvez-vous nous aider ?")
		$globalNotice = $skin->msg( 'globalnotice' );
		if ( !$globalNotice->isDisabled() ) {
			// Give the global notice its own ID and center it
			$ourSiteNotice .= '<div style="text-align: center;" id="globalNotice">' .
				$globalNotice->parseAsBlock() . '</div>';
		}

		$user = $skin->getUser();
		// Group-specific global notices
		foreach ( [ 'sysop', 'bureaucrat', 'bot', 'rollback' ] as $group ) {
			$messageName = 'globalnotice-' . $group;
			$globalNoticeForGroup = $skin->msg( $messageName );
			$isMember = in_array( $group, $this->userGroupManager->getUserEffectiveGroups( $user ) );
			if ( !$globalNoticeForGroup->isDisabled() && $isMember ) {
				// Give the global notice its own ID and center it
				$ourSiteNotice .= '<div style="text-align: center;" id="globalNoticeForGroup">' .
					$globalNoticeForGroup->parseAsBlock() . '</div>';
			}
		}

		// If we have something to display, wrap the local sitenotice in a pretty
		// div and copy $ourSiteNotice to $siteNotice
		if ( !empty( $ourSiteNotice ) ) {
			$ourSiteNotice .= '<!-- end GlobalNotice --><div id="localSiteNotice">' . $siteNotice . '</div>';
			$siteNotice = $ourSiteNotice;
		}

		return true;
	}

	/**
	 * Show an annoying warning when editing MediaWiki:Forced-globalnotice because
	 * that message is Serious Business™.
	 *
	 * Disabled for production by default (but can be configured),
	 * might be too annoying -- but I just wanted to code this feature. :)
	 *
	 * @param EditPage &$editPage Instance of EditPage class
	 * @return bool
	 */
	public static function displayWarningOnEditPage( &$editPage ) {
		global $wgGlobalNoticeDisplayWarningOnEditPage;

		if ( !$wgGlobalNoticeDisplayWarningOnEditPage ) {
			return true;
		}

		// only initialize this when editing pages in MediaWiki namespace
		if ( $editPage->mTitle->getNamespace() != 8 ) {
			return true;
		}

		// Show an annoying warning when editing MediaWiki:Forced-globalnotice
		// I considered using confirm() JS but it doesn't allow CSS properties
		// AFAIK and no CSS properties = less obtrusive notice = bad, so I ditched
		// that idea.
		if ( $editPage->mTitle->getDBkey() == 'Forced-globalnotice' ) {
			$editPage->editFormPageTop .= '<span style="color: red;">Hey, hold it right there!</span><br />
	The value of this message is shown to <strong>all users</strong>, no matter what is their language. This can be <strong>extremely</strong> annoying.<br />
	<span style="text-transform: uppercase; font-size: 20px;">Only use this for really important things, like server maintenance notices!</span><br />
	Understood?
	<br /><br />

	<a href="#" onclick="document.getElementById( \'wpTextbox1\' ).style.display = \'block\'; return false;">Yes!</a>';
			// JavaScript must be injected here, wpTextbox1 doesn't exist before...
			$editPage->editFormTextAfterWarn .= '<script type="text/javascript">
				document.getElementById( \'wpTextbox1\' ).style.display = \'none\';
			</script>';
		}

		return true;
	}

}
