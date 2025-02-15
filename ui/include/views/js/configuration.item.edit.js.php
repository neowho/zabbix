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


/**
 * @var CView $this
 */

include __DIR__.'/common.item.edit.js.php';
include __DIR__.'/item.preprocessing.js.php';
include __DIR__.'/editabletable.js.php';
include __DIR__.'/itemtest.js.php';
?>
<script>
	const view = {
		form_name: null,
		context: null,

		init({form_name, trends_default, context}) {
			this.form_name = form_name;
			this.context = context;

			// Field switchers.
			new CViewSwitcher('value_type', 'change', item_form.field_switches.for_value_type);

			const $value_type = $('#value_type');

			$('#type')
				.change(this.typeChangeHandler)
				.trigger('change');

			// Whenever non-numeric type is changed back to numeric type, set the default value in "trends" field.
			$value_type
				.change(function() {
					const old_value = $(this).data('old-value');
					const new_value = $(this).val();
					const $trends = $('#trends');

					if ((old_value == <?= ITEM_VALUE_TYPE_STR ?> || old_value == <?= ITEM_VALUE_TYPE_LOG ?>
							|| old_value == <?= ITEM_VALUE_TYPE_TEXT ?>)
							&& (new_value == <?= ITEM_VALUE_TYPE_FLOAT ?>
							|| new_value == <?= ITEM_VALUE_TYPE_UINT64 ?>)) {
						if ($trends.val() == 0) {
							$trends.val(trends_default);
						}

						$('#trends_mode_1').prop('checked', true);
					}

					$('#trends_mode').trigger('change');
					$(this).data('old-value', new_value);
				})
				.data('old-value', $value_type.val());

			$('#history_mode')
				.change(function() {
					if ($('[name="history_mode"][value=' + <?= ITEM_STORAGE_OFF ?> + ']').is(':checked')) {
						$('#history').prop('disabled', true).hide();
						$('#history_mode_hint').hide();
					}
					else {
						$('#history').prop('disabled', false).show();
						$('#history_mode_hint').show();
					}
				})
				.trigger('change');

			$('#trends_mode')
				.change(function() {
					if ($('[name="trends_mode"][value=' + <?= ITEM_STORAGE_OFF ?> + ']').is(':checked')) {
						$('#trends').prop('disabled', true).hide();
						$('#trends_mode_hint').hide();
					}
					else {
						$('#trends').prop('disabled', false).show();
						$('#trends_mode_hint').show();
					}
				})
				.trigger('change');
		},

		typeChangeHandler() {
			// Selected item type.
			const type = parseInt($('#type').val(), 10);
			const has_key_button = [ <?= ITEM_TYPE_ZABBIX ?>, <?= ITEM_TYPE_ZABBIX_ACTIVE ?>, <?= ITEM_TYPE_SIMPLE ?>,
				<?= ITEM_TYPE_INTERNAL ?>, <?= ITEM_TYPE_DB_MONITOR ?>, <?= ITEM_TYPE_SNMPTRAP ?>, <?= ITEM_TYPE_JMX ?>,
				<?= ITEM_TYPE_IPMI ?>
			];

			$('#keyButton').prop('disabled', !has_key_button.includes(type));

			if (type == <?= ITEM_TYPE_SSH ?> || type == <?= ITEM_TYPE_TELNET ?>) {
				$('label[for=username]').addClass('<?= ZBX_STYLE_FIELD_LABEL_ASTERISK ?>');
				$('input[name=username]').attr('aria-required', 'true');
			}
			else {
				$('label[for=username]').removeClass('<?= ZBX_STYLE_FIELD_LABEL_ASTERISK ?>');
				$('input[name=username]').removeAttr('aria-required');
			}
		},

		checkNow(button) {
			button.classList.add('is-loading');

			const curl = new Curl('zabbix.php');
			curl.setArgument('action', 'item.masscheck_now');
			curl.setArgument('<?= CCsrfTokenHelper::CSRF_TOKEN_NAME ?>',
				<?= json_encode(CCsrfTokenHelper::get('item')) ?>
			);

			fetch(curl.getUrl(), {
				method: 'POST',
				headers: {'Content-Type': 'application/json'},
				body: JSON.stringify({itemids: [document.getElementById('itemid').value]})
			})
				.then((response) => response.json())
				.then((response) => {
					clearMessages();

					/*
					 * Using postMessageError or postMessageOk would mean that those messages are stored in session
					 * messages and that would mean to reload the page and show them. Also postMessageError would be
					 * displayed right after header is loaded. Meaning message is not inside the page form like that is
					 * in postMessageOk case. Instead show message directly that comes from controller.
					 */
					if ('error' in response) {
						addMessage(makeMessageBox('bad', [response.error.messages], response.error.title, true, true));
					}
					else if('success' in response) {
						addMessage(makeMessageBox('good', [], response.success.title, true, false));
					}
				})
				.catch(() => {
					const title = <?= json_encode(_('Unexpected server error.')) ?>;
					const message_box = makeMessageBox('bad', [], title)[0];

					clearMessages();
					addMessage(message_box);
				})
				.finally(() => {
					button.classList.remove('is-loading');

					// Deselect the "Execute now" button in both success and error cases, since there is no page reload.
					button.blur();
				});
		},

		editHost(e, hostid) {
			e.preventDefault();
			const host_data = {hostid};

			this.openHostPopup(host_data);
		},

		openHostPopup(host_data) {
			const original_url = location.href;
			const overlay = PopUp('popup.host.edit', host_data, {
				dialogueid: 'host_edit',
				dialogue_class: 'modal-popup-large',
				prevent_navigation: true
			});

			overlay.$dialogue[0].addEventListener('dialogue.submit',
				this.events.elementSuccess.bind(this, this.context), {once: true}
			)
			overlay.$dialogue[0].addEventListener('dialogue.close', () => {
				history.replaceState({}, '', original_url);
			}, {once: true});
		},

		editTemplate(e, templateid) {
			e.preventDefault();
			const template_data = {templateid};

			this.openTemplatePopup(template_data);
		},

		openTemplatePopup(template_data) {
			const overlay =  PopUp('template.edit', template_data, {
				dialogueid: 'templates-form',
				dialogue_class: 'modal-popup-large',
				prevent_navigation: true
			});

			overlay.$dialogue[0].addEventListener('dialogue.submit',
				this.events.elementSuccess.bind(this, this.context), {once: true}
			);
		},

		refresh() {
			const url = new Curl('');
			const form = document.getElementsByName(this.form_name)[0];
			const fields = getFormFields(form);

			post(url.getUrl(), fields);
		},

		events: {
			elementSuccess(context, e) {
				const data = e.detail;
				let curl = null;

				if ('success' in data) {
					postMessageOk(data.success.title);

					if ('messages' in data.success) {
						postMessageDetails('success', data.success.messages);
					}

					if ('action' in data.success && data.success.action === 'delete') {
						curl = new Curl('items.php');
						curl.setArgument('context', context);
						curl.setArgument('filter_set', 1);
					}
				}

				if (curl == null) {
					view.refresh();
				}
				else {
					location.href = curl.getUrl();
				}
			}
		}
	};
</script>
