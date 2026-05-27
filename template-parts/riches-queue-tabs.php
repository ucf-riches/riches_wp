<?php
/**
 * Tabbed category queues (Bootstrap 4 tabs).
 *
 * Expects $GLOBALS['riches_queue_tabs_ctx'] with keys:
 * queues, active_index, root_id, tab_ids, panel_ids
 *
 * @package UCF-WordPress-Theme-child-RICHES
 */

if ( ! isset( $GLOBALS['riches_queue_tabs_ctx'] ) || ! is_array( $GLOBALS['riches_queue_tabs_ctx'] ) ) {
	return;
}

$ctx = $GLOBALS['riches_queue_tabs_ctx'];
$queues        = isset( $ctx['queues'] ) ? $ctx['queues'] : array();
$active_index  = isset( $ctx['active_index'] ) ? (int) $ctx['active_index'] : 0;
$root_id       = isset( $ctx['root_id'] ) ? $ctx['root_id'] : 'riches-queue-tabs';
$tab_ids       = isset( $ctx['tab_ids'] ) ? $ctx['tab_ids'] : array();
$panel_ids     = isset( $ctx['panel_ids'] ) ? $ctx['panel_ids'] : array();

if ( empty( $queues ) ) {
	return;
}
?>
<div class="riches-queue-tabs" id="<?php echo esc_attr( $root_id ); ?>">
	<div class="container mt-4 mt-sm-5 pb-0 riches-queue-tabs__toolbar">
		<ul class="nav nav-tabs flex-nowrap riches-queue-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Content queues', 'UCF-WordPress-Theme-child-RICHES' ); ?>">
			<?php foreach ( $queues as $i => $row ) : ?>
				<?php
				$tab_id    = isset( $tab_ids[ $i ] ) ? $tab_ids[ $i ] : $root_id . '-tab-' . $i;
				$panel_id  = isset( $panel_ids[ $i ] ) ? $panel_ids[ $i ] : $root_id . '-panel-' . $i;
				$is_active = ( $i === $active_index );
				$tab_label = $row['label'] !== '' ? $row['label'] : $row['category'];
				?>
			<li class="nav-item" role="presentation">
				<a
					class="nav-link <?php echo $is_active ? 'active' : ''; ?>"
					id="<?php echo esc_attr( $tab_id ); ?>"
					data-toggle="tab"
					href="#<?php echo esc_attr( $panel_id ); ?>"
					role="tab"
					aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
					tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
				><?php echo esc_html( $tab_label ); ?></a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="riches-collections riches-queue-tabs__collections">
		<div class="container">
			<div class="tab-content riches-queue-tabs__panels">
				<?php foreach ( $queues as $i => $row ) : ?>
					<?php
					$panel_id  = isset( $panel_ids[ $i ] ) ? $panel_ids[ $i ] : 'riches-qt-panel-' . $i;
					$tab_id    = isset( $tab_ids[ $i ] ) ? $tab_ids[ $i ] : 'riches-qt-tab-' . $i;
					$is_active = ( $i === $active_index );
					?>
				<div
					class="tab-pane fade <?php echo $is_active ? 'show active' : ''; ?>"
					id="<?php echo esc_attr( $panel_id ); ?>"
					role="tabpanel"
					aria-labelledby="<?php echo esc_attr( $tab_id ); ?>"
				>
					<?php
					echo riches_render_category_queue(
						array(
							'category'          => $row['category'],
							'label'             => $row['label'],
							'posts_per_page'    => $row['posts_per_page'],
							'show_heading'      => false,
							'reduced'           => ! empty( $row['reduced'] ),
							'wrap_collections'  => false,
							'include_container' => false,
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
