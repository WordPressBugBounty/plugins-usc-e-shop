jQuery(document).ready(function ($) {
	if ($('#progress-container').length > 0) {
		window.onbeforeunload = function () {
			return "インポート処理中です。";
		};

		function processChunk(offset) {
			$.ajax({
				url: wel_csv_importer_data.ajax_url,
				method: 'POST',
				data: {
					action: 'process_csv_chunk',
					type: window.wel_csv_importer_type,
					import_id: window.wel_csv_importer_importId,
					offset: offset,
					_wpnonce: window.wel_csv_importer_nonce
				},
				success: function (res) {
					if (res.success) {
						$('#progress-bar').css('width', res.data.percent + '%');
						$('#progress-text').text(res.data.percent + '%');
						$('#processed-rows').text(res.data.next_offset);
						$('#inserted-count').text(res.data.added);   // 追加件数を更新
						$('#updated-count').text(res.data.updated);  // 更新件数を更新
						$('#estimated-time').text(res.data.estimated_time);
						$('#memory_usage').text(res.data.memory_usage);
						if (res.data.complete) {
							// 完了 => 離脱警告解除
							window.onbeforeunload = null;

							var adminUrl = wel_csv_importer_data.admin_url;
							if (adminUrl.indexOf('?') === -1) {
								adminUrl += '?';
							} else {
								adminUrl += '&';
							}

							var redirectUrl = adminUrl +
								"page=welcart-csv-importer&step=result" +
								"&type=" + encodeURIComponent((window.wel_csv_importer_type || '').trim()) +
								"&import_id=" + encodeURIComponent((window.wel_csv_importer_importId || '').trim()) +
								"&wc_nonce=" + encodeURIComponent((window.wel_csv_complete_nonce || '').trim());
							window.location.href = redirectUrl;
						} else {
							processChunk(res.data.next_offset);
						}
					} else {
						$('#import-result').html('<p style="color:red;">Error: ' + res.data.message + '</p>');
					}
				},
				error: function (xhr, status, error) {
					$('#import-result').html('<p style="color:red;">AJAXエラー: ' + error + '</p>');
				}
			});
		}
		// 初期オフセット
		var initialOffset = wel_csv_importer_data.initial_offset || 0;
		processChunk(initialOffset);
	}
});
