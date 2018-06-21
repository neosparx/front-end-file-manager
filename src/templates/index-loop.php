<div id="fefm-wrap">
	<ul id="fefm-controls">
		<li><a class="fefm-controls-btn" id="fefm-controls-btn-uploaded" href="#">Upload</a></li>
		<li><a class="fefm-controls-btn fefm-controls-btn-link" href="#">New Folder</a></li>
	</ul>

	
	<ul id="fefm-uploader-file-list"></ul>

	<ul id="fefm-navigation">
		<li>
			<img width="24" src="<?php echo plugins_url();?>/front-end-file-manager/public/images/file-type-icons/folder-open.svg">
		</li>
		<li class="fefm-search-dir-wrap">
			<div class="fefm-search-dir-inner-wrap">
				<form action="" method="GET" id="fefm-search-form">
					<input type="search" autocomplete="off" name="fefm-file-dir-search" id="fefm-file-dir-search" 
						placeholder="<?php esc_html_e('Search', 'front-end-file-manager'); ?>">
				</form>
				<span id="fefm-search-close-search">&times;</span>
			</div>
		</li>
	</ul>

	<ul id="fefm-file-actions">
		<li class="file-actions-check-selector">
			<input type="checkbox" />
		</li>
		<li class="file-actions-check-title">
			<a href="#" id="fefm-action-sort-by-title">Title</a>
		</li>
		<li class="file-actions-check-last-modified">
			<a href="#" id="fefm-action-sort-by-updated">Last Modified</a>
		</li>
		<li class="file-actions-check-sharing-type">
			Sharing
		</li>
		<li class="file-actions-check-actions">
			Actions 
		</li>
	</ul>

	<ul id="fefm-wrap-ul">
	</ul>
</div>
<div id="fefm-single-view-wrap"></div>
<div id="fefm-pagination-wrap"></div>

<script id="fefm-single-view" type="text/template">
	<?php include_once trailingslashit( FEFM_DIR ) . 'src/templates/single-view.php'; ?>
</script>
<script id="fefm-single-file-template" type="text/template">
	<?php include_once trailingslashit( FEFM_DIR ) . 'src/templates/list.php'; ?>
</script>
<script id="fefm-pagination-template" type="text/template">
	<?php include_once trailingslashit( FEFM_DIR ) . 'src/templates/pagination.php'; ?>
</script>