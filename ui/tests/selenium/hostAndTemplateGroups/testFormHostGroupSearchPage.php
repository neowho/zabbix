<?php
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/../common/testFormGroups.php';

/**
 * @backup hosts
 *
 * @onBefore prepareGroupData
 *
 * @dataSource DiscoveredHosts, HostTemplateGroups
 */
class testFormHostGroupSearchPage extends testFormGroups {

	protected $link = 'zabbix.php?action=search&search=group';
	protected $object = 'host';
	protected $search = 'true';
	protected static $update_group = 'Group for Update test';

	public function testFormHostGroupSearchPage_Layout() {
		$this->link = 'zabbix.php?action=search&search=Zabbix+servers';
		$this->layout('Zabbix servers');
	}

	public function testFormHostGroupSearchPage_DiscoveredLayout() {
		$this->link = 'zabbix.php?action=search&search='.self::DISCOVERED_GROUP;
		$this->layout(self::DISCOVERED_GROUP, true);
	}

	public static function getHostValidationData() {
		return [
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Group name' => self::DISCOVERED_GROUP
					],
					'error' => 'Host group "'.self::DISCOVERED_GROUP.'" already exists.'
				]
			]
		];
	}

	public static function getHostUpdateData() {
		return [
			[
				[
					'expected' => TEST_BAD,
					'fields' => [
						'Group name' => 'Zabbix servers',
						'Apply permissions and tag filters to all subgroups' => true
					],
					'error' => 'Host group "Zabbix servers" already exists.'
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Group name' => 'Templates/Update'
					]
				]
			],
			[
				[
					'expected' => TEST_GOOD,
					'fields' => [
						'Group name' => str_repeat('updat', 51)
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getUpdateData
	 * @dataProvider getHostValidationData
	 * @dataProvider getHostUpdateData
	 */
	public function testFormHostGroupSearchPage_Update($data) {
		$this->link = 'zabbix.php?action=search&search=updat';
		$this->checkForm($data, 'update');
	}

	/**
	 * Test group simple update without changing data.
	 */
	public function testFormHostGroupSearchPage_SimpleUpdate() {
		$this->link = 'zabbix.php?action=search&search='.self::DISCOVERED_GROUP;
		$this->simpleUpdate(self::DISCOVERED_GROUP, true);
	}

	public static function getHostCloneData() {
		return [
			[
				[
					'expected' => TEST_GOOD,
					'name' => self::DISCOVERED_GROUP,
					'fields' => [
						'Group name' => self::DISCOVERED_GROUP.' cloned group'
					],
					'discovered' => true
				]
			]
		];
	}

	/**
	 * @dataProvider getCloneData
	 * @dataProvider getHostCloneData
	 */
	public function testFormHostGroupSearchPage_Clone($data) {
		$this->clone($data);
	}

	/**
	 * @dataProvider getCancelData
	 */
	public function testFormHostGroupSearchPage_Cancel($data) {
		$this->cancel($data);
	}

	public static function getHostDeleteData() {
		return [
			[
				[
					'expected' => TEST_BAD,
					'name' => self::DELETE_ONE_GROUP,
					'error' => 'Host "Host for host group testing" cannot be without host group.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Group for Maintenance',
					'error' => 'Cannot delete host group "Group for Maintenance" because maintenance'.
						' "Maintenance for host group testing" must contain at least one host or host group.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Group for Correlation',
					'error' => 'Group "Group for Correlation" cannot be deleted, because it is used in a correlation condition.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Group for Script',
					'error' => 'Host group "Group for Script" cannot be deleted, because it is used in a global script.'
				]
			],
			[
				[
					'expected' => TEST_BAD,
					'name' => 'Group for Host prototype',
					'error' => 'Group "Group for Host prototype" cannot be deleted, because it is used by a host prototype.'
				]
			]
		];
	}

	/**
	 * @dataProvider getDeleteData
	 * @dataProvider getHostDeleteData
	 */
	public function testFormHostGroupSearchPage_Delete($data) {
		$this->delete($data);
	}

	public static function getSubgroupPermissionsData() {
		return [
			[
				[
					'apply_permissions' => 'Europe',
					// Permission inheritance doesn't apply when changing the name of existing group.
					'open_form' => 'Europe group for test on search page',
					'create' => 'Streets/Dzelzavas',
					'groups_after' => [
						'Cities/Cesis' => 'Read',
						'Europe (including subgroups)' => 'Deny',
						'Streets' => 'Deny',
						'Streets/Dzelzavas' => 'None'
					],
					'tags_after' => [
						['Host group' => 'Cities/Cesis', 'Tags' => 'city: Cesis'],
						['Host group' => 'Europe', 'Tags' => 'world'],
						['Host group' => 'Europe/Latvia', 'Tags' => 'world'],
						['Host group' => 'Europe/Latvia/Riga/Zabbix', 'Tags' => 'world'],
						['Host group' => 'Europe/Test', 'Tags' => 'world'],
						['Host group' => 'Europe/Test/Zabbix', 'Tags' => 'world'],
						['Host group' => 'Streets', 'Tags' => 'street']
					]
				]
			]
		];
	}

	/**
	 * @onBeforeOnce prepareSubgroupData
	 * @dataProvider getSubgroupPermissionsData
	 */
	public function testFormHostGroupSearchPage_ApplyPermissionsToSubgroups($data) {
		$this->link = 'zabbix.php?action=search&search=europe';
		$this->checkSubgroupsPermissions($data);
	}
}
