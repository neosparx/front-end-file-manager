<div id="fefm-wrap">

	<ul id="fefm-controls">
		<li><a class="fefm-controls-btn" id="fefm-controls-btn-uploaded" href="#">Upload</a></li>
		<li><a class="fefm-controls-btn fefm-controls-btn-link" href="#">New Folder</a></li>
	</ul>

	<ul id="filelist"></ul>

	<ul id="fefm-navigation">
		<li>
			<img width="24" src="<?php echo plugins_url();?>/front-end-file-manager/public/images/file-type-icons/folder-open.svg">
		</li>
		<li>Found 12 files in root folder</li>
	</ul>
	<ul id="fefm-wrap-ul">
	</ul>
</div>

<script id="fefm-single-file-template" type="text/template">
	<li class="file-item">
		<div class="file-item-column file-thumb">
			<img width="24" src="<%=file_icon%>" />
		</div>
		<div class="file-item-column file-label">
			<a href="#file/<%=id%>" title="<%=file_label%>">
				<%=file_label%>
			</a>
		</div>
		<div class="file-item-column file-date-updated"><%=date_updated%></div>
		<div class="file-item-column file-sharing-type"><%=file_sharing_type%></div>
		<div class="file-item-column file-actions">
			<a href="#" class="file-item-column-file-actions-dropdown">
				<span class="fefm-action-label">Select Actions</span>
			</a>
			<ul class="file-item-column-file-actions-dropdown-ul inactive">
				<li><a href="#">Edit</a></li>
				<li><a href="#">Copy</a></li>
				<li><a href="#">Share</a></li>
				<li><a data-file-id="<%=id%>" class="fefm-item-file-trash" href="#">Trash</a></li>
				<li><a class="fefm-toolbar-close" href="#">Close Toolbar</a></li>
			</ul>
		</div>
	</li>
</script>