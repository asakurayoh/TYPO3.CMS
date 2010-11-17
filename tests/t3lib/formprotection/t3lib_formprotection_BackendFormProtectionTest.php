<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Oliver Klee (typo3-coding@oliverklee.de)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for the t3lib_formprotection_BackendFormProtection class.
 *
 * $Id$
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class t3lib_formprotection_BackendFormProtectionTest extends tx_phpunit_testcase {
	/**
	 * a backup of the current BE user
	 *
	 * @var t3lib_beUserAuth
	 */
	private $backEndUserBackup = NULL;

	/**
	 * @var t3lib_formprotection_BackendFormProtection
	 */
	private $fixture;

	public function setUp() {
		$this->backEndUserBackup = $GLOBALS['BE_USER'];
		$GLOBALS['BE_USER'] = $this->getMock(
			't3lib_beUserAuth',
			array('getSessionData', 'setAndSaveSessionData')
		);

		$className = $this->createAccessibleProxyClass();
		$this->fixture = new $className;
	}

	public function tearDown() {
		$this->fixture->__destruct();
		unset($this->fixture);

		$GLOBALS['BE_USER'] = $this->backEndUserBackup;

		t3lib_FlashMessageQueue::getAllMessagesAndFlush();
	}


	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Creates a subclass t3lib_formprotection_BackendFormProtection with retrieveTokens made
	 * public.
	 *
	 * @return string the name of the created class, will not be empty
	 */
	private function createAccessibleProxyClass() {
		$className = 't3lib_formprotection_BackendFormProtectionAccessibleProxy';
		if (!class_exists($className)) {
			eval(
				'class ' . $className . ' extends t3lib_formprotection_BackendFormProtection {' .
				'  public function createValidationErrorMessage() {' .
				'    parent::createValidationErrorMessage();' .
				'  }' .
				'  public function retrieveTokens() {' .
				'    return parent::retrieveTokens();' .
				'  }' .
				'}'
			);
		}

		return $className;
	}


	////////////////////////////////////
	// Tests for the utility functions
	////////////////////////////////////

	/**
	 * @test
	 */
	public function createAccessibleProxyCreatesBackendFormProtectionSubclass() {
		$className = $this->createAccessibleProxyClass();

		$this->assertTrue(
			(new $className()) instanceof t3lib_formprotection_BackendFormProtection
		);
	}


	//////////////////////////////////////////////////////////
	// Tests concerning the reading and saving of the tokens
	//////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function retrieveTokensReadsTokensFromSessionData() {
		$GLOBALS['BE_USER']->expects($this->once())->method('getSessionData')
			->with('formTokens')->will($this->returnValue(array()));

		$this->fixture->retrieveTokens();
	}

	/**
	 * @test
	 */
	public function tokensFromSessionDataAreAvailableForValidateToken() {
		$tokenId = '51a655b55c54d54e5454c5f521f6552a';
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = '42';

		$GLOBALS['BE_USER']->expects($this->once())->method('getSessionData')
			->with('formTokens')->will($this->returnValue(array(
				$tokenId => array(
					'formName' => $formName,
					'action' => $action,
					'formInstanceName' => $formInstanceName,
				),
			)));

		$this->fixture->retrieveTokens();

		$this->assertTrue(
			$this->fixture->validateToken($tokenId, $formName, $action,  $formInstanceName)
		);
	}

	/**
	 * @test
	 */
	public function persistTokensWritesTokensToSession() {
		$formName = 'foo';
		$action = 'edit';
		$formInstanceName = '42';

		$tokenId = $this->fixture->generateToken(
			$formName, $action, $formInstanceName
		);
		$allTokens = array(
			$tokenId => array(
					'formName' => $formName,
					'action' => $action,
					'formInstanceName' => $formInstanceName,
				),
		);

		$GLOBALS['BE_USER']->expects($this->once())
			->method('setAndSaveSessionData')->with('formTokens', $allTokens);

		$this->fixture->persistTokens();
	}


	//////////////////////////////////////////////////
	// Tests concerning createValidationErrorMessage
	//////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function createValidationErrorMessageAddsErrorFlashMessage() {
		$this->fixture->createValidationErrorMessage();

		$messages = t3lib_FlashMessageQueue::getAllMessagesAndFlush();
		$this->assertContains(
			$GLOBALS['LANG']->sL(
				'LLL:EXT:lang/locallang_core.xml:error.formProtection.tokenInvalid'
			),
			$messages[0]->render()
		);
	}
}
?>