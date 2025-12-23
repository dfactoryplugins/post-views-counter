<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Settings_Integrations class.
 *
 * @class Post_Views_Counter_Settings_Integrations
 */
class Post_Views_Counter_Settings_Integrations {

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		return [
			'post_views_counter_integrations' => [
				'tab'			=> 'integrations',
				'title'			=> '',
				'callback'		=> null
			]
		];
	}

	/**
	 * Get fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		$fields = [];
		$integrations = Post_Views_Counter_Integrations::get_integrations();

		foreach ( $integrations as $slug => $integration ) {
			$field = [
				'tab'			=> 'integrations',
				'section'		=> 'post_views_counter_integrations',
				'id'			=> 'pvc-integration_' . $slug,
				'type'			=> 'custom',
				'title'			=> $integration['name'],
				'callback'		=> [ $this, 'integration_field' ],
				'skip_saving'	=> true,
				'slug'			=> $slug
			];

			if ( isset( $integration['pro'] ) && $integration['pro'] && ! class_exists( 'Post_Views_Counter_Pro' ) ) {
				$field['class'] = 'pvc-pro';
			}

			$fields[ $slug ] = $field;
		}

		return $fields;
	}

	/**
	 * Custom integration field.
	 *
	 * @param array $field
	 * @return string
	 */
	public function integration_field( $field ) {
		$slug = $field['slug'];
		$integrations = Post_Views_Counter_Integrations::get_integrations();
		$integration = $integrations[$slug];

		$checked = $integration['status'] ? 'checked' : '';
		$disabled = ! $integration['availability'] ? 'disabled' : '';

		$classes = [ 'pvc-integration-content' ];
		if ( ! $integration['availability'] )
			$classes[] = 'unavailable';

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		$html .= '<label>';
		$html .= '<input type="checkbox" name="post_views_counter_settings_integrations[integrations][' . esc_attr( $slug ) . ']" value="1" ' . $checked . ' ' . $disabled . ' /> ';
		$html .= esc_html( $integration['description'] );
		$html .= '</label>';

		if ( ! empty( $integration['items'] ) ) {
			$html .= '<ul class="pvc-integration-items">';
			foreach ( $integration['items'] as $item ) {
				$html .= '<li><strong>' . esc_html( $item['name'] ) . ':</strong> ' . esc_html( $item['description'] ) . '</li>';
			}
			$html .= '</ul>';
		}

		$html .= '</div>';

		return $html;
	}
}
