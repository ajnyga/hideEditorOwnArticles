<?php

/**
 * @file plugins/generic/hideEditorOwnArticles/hideEditorOwnArticlesPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2007-2009 Juan Pablo Alperin, Gunther Eysenbach
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class hideEditorOwnArticlesPlugin
 * @ingroup plugins_generic_hideEditorOwnArticles
 *
 * @brief hideEditorOwnArticles plugin class
 * 
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class hideEditorOwnArticlesPlugin extends GenericPlugin {

	function register($category, $path) {

		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {

			# Hide reviewer name
			if (Request::getRequestedOp() == 'submissionReview') {
				HookRegistry::register('ReviewAssignmentDAO::_fromRow', array($this, 'hideReviewerNames'));
			}
			
			# Hide logs 
			HookRegistry::register('EmailLogDAO::build', array($this, 'hideEmailLog'));
			HookRegistry::register('EventLogDAO::build', array($this, 'hideEventLog'));
			
			# Hook user profile edit, if new email > update all author table matching emails ???? problems?
						
		}

	
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.hideEditorOwnArticles.displayName');
	}
	
	function getDescription() {
		return __('plugins.generic.hideEditorOwnArticles.description');
	}
	
	function hideEmailLog($hookName, $args) {
		$entry =& $args[0];		
		$submissionId = $entry->_data[assocId];
		if ($this->isOwnArticle($submissionId) == 1){
			$entry->_data = array('from' => "Hidden (own article)", 'recipients' => "Hidden (own article)");
		}
		return false;
	}

	function hideEventLog($hookName, $args) {
		$entry =& $args[0];		
		$submissionId = $entry->_data[assocId];
		if ($this->isOwnArticle($submissionId) == 1){
			$entry->_data['params'][reviewerName] = "[Hidden]";
		}
		return false;
	}	
	
	function hideReviewerNames($hookName, $args) {
		$reviewAssignment =& $args[0];
		$submissionId = $reviewAssignment->_data['submissionId'];
		if ($this->isOwnArticle($submissionId) == 1){
			$reviewAssignment->_data = array('reviewerFullName' => "Hidden (own article)");
		}	
		return false;
	}

	function isOwnArticle($submissionId){
		
		$isOwnArticle = 0;
		
		# Other options for comparing user account and author account?
		
		$user = Request::getUser();
		$user_email = $user->getEmail();
		$user_firstname = $user->getFirstName();
		$user_lastname = $user->getLastName();
		
		$authorDao =& DAORegistry::getDAO('AuthorDAO');
		$authors =& $authorDao->getAuthorsBySubmissionId($submissionId);
		
		
		# If emails match or if first name and last name match. The latter could be problematic.
		
		foreach ($authors as $author) {
			if ($author->getEmail() == $user_email OR ($author->getLastName() == $user_lastname AND $author->getFirstName == $user_firstname ) )
				$isOwnArticle = 1;
		}

		return $isOwnArticle;
		
	}
 
	
} 


?>
