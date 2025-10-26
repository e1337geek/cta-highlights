<?php
/**
 * Storage condition row partial
 *
 * @package CTAHighlights
 * @var array $condition Condition data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$condition = isset( $condition ) ? $condition : array(
	'key'      => '',
	'operator' => '=',
	'value'    => '',
	'datatype' => 'string',
);
?>

<div class="storage-condition-row">
	<input type="text" name="storage_condition_key[]" placeholder="<?php esc_attr_e( 'localStorage key', 'cta-highlights' ); ?>" value="<?php echo esc_attr( $condition['key'] ); ?>">

	<select name="storage_condition_operator[]">
		<option value="=" <?php selected( $condition['operator'], '=' ); ?>>=</option>
		<option value="!=" <?php selected( $condition['operator'], '!=' ); ?>>!=</option>
		<option value=">" <?php selected( $condition['operator'], '>' ); ?>>></option>
		<option value="<" <?php selected( $condition['operator'], '<' ); ?>><</option>
		<option value=">=" <?php selected( $condition['operator'], '>=' ); ?>>>=</option>
		<option value="<=" <?php selected( $condition['operator'], '<=' ); ?>><=</option>
	</select>

	<input type="text" name="storage_condition_value[]" placeholder="<?php esc_attr_e( 'Value', 'cta-highlights' ); ?>" value="<?php echo esc_attr( $condition['value'] ); ?>">

	<select name="storage_condition_datatype[]">
		<option value="string" <?php selected( $condition['datatype'], 'string' ); ?>><?php esc_html_e( 'String', 'cta-highlights' ); ?></option>
		<option value="number" <?php selected( $condition['datatype'], 'number' ); ?>><?php esc_html_e( 'Number', 'cta-highlights' ); ?></option>
		<option value="boolean" <?php selected( $condition['datatype'], 'boolean' ); ?>><?php esc_html_e( 'Boolean', 'cta-highlights' ); ?></option>
		<option value="date" <?php selected( $condition['datatype'], 'date' ); ?>><?php esc_html_e( 'Date', 'cta-highlights' ); ?></option>
		<option value="regex" <?php selected( $condition['datatype'], 'regex' ); ?>><?php esc_html_e( 'Regex', 'cta-highlights' ); ?></option>
	</select>

	<button type="button" class="button remove-storage-condition"><?php esc_html_e( 'Remove', 'cta-highlights' ); ?></button>
</div>
