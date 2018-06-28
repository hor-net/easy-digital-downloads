<?php
/**
 * Reports API - Chart Manifest class
 *
 * @package     EDD
 * @subpackage  Reports\Data\Charts
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */namespace EDD\Reports\Data\Charts\v2;

use EDD\Reports;
use EDD\Reports\Data\Chart_Endpoint;
use EDD\Utils\Error_Logger_Interface as Error_Logger;

/**
 * Represents a manifestation of a ChartJS v2 object's attributes in PHP form.
 *
 * Primarily used to simplify translating server-side arguments into client-side ones.
 *
 * @since 3.0
 */
class Manifest implements Error_Logger {

	/**
	 * Represents the chart type to be manifested.
	 *
	 * @since 3.0
	 * @var   string
	 */
	private $type;

	/**
	 * Represents the unfiltered chart options for the manifest.
	 *
	 * @since 3.0
	 * @var   array
	 */
	private $options = array();

	/**
	 * Datasets associated with the current chart.
	 *
	 * @since 3.0
	 * @var   Dataset[]
	 */
	private $datasets = array();

	/**
	 * Labels associated with the current pie or doughnut chart.
	 *
	 * @since 3.0
	 * @var   array
	 */
	private $labels = array();

	/**
	 * Represents the current Chart_Endpoint instance.
	 *
	 * @since 3.0
	 * @var   Chart_Endpoint
	 */
	private $endpoint;

	/**
	 * Holds errors related to instantiating the manifest.
	 *
	 * @since 3.0
	 * @var   \WP_Error
	 */
	protected $errors;

	/**
	 * Sets up the manifest.
	 *
	 * @since 3.0
	 *
	 * @param Chart_Endpoint $endpoint Chart endpoint.
	 */
	public function __construct( $endpoint ) {
		$this->setup_error_logger();

		$this->set_type( $endpoint->get_type() );
		$this->set_endpoint( $endpoint );

		$options = $endpoint->get_options();

		if ( $this->is_pie_manifest() && ! empty( $options['labels'] ) ) {
			$this->set_labels( $options['labels'] );

			unset( $options['labels'] );
		}

		$this->set_options( $options );
	}

	/**
	 * Retrieves the chart type.
	 *
	 * @since 3.0
	 *
	 * @return string Chart type.
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Sets the chart type for the manifest.
	 *
	 * @since 3.0
	 *
	 * @param string $type Chart type to be manifested.
	 */
	private function set_type( $type ) {
		$this->type = sanitize_key( $type );
	}

	/**
	 * Retrieves the chart endpoint object for this manifest.
	 *
	 * @since 3.0
	 *
	 * @return Chart_Endpoint Chart endpoint.
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * Sets the chart endpoint object.
	 *
	 * @since 3.0
	 *
	 * @param EDD\Reports\Data\Chart_Endpoint $endpoint Chart_Endpoint object.
	 */
	private function set_endpoint( $endpoint ) {
		$this->endpoint = $endpoint;
	}

	/**
	 * Stores the unfiltered chart options for later access.
	 *
	 * @since 3.0
	 *
	 * @param array $options Chart options and datasets.
	 */
	private function set_options( $options ) {
		if ( ! empty( $options['datasets'] ) && is_array( $options['datasets'] ) ) {

			foreach ( $options['datasets'] as $id => $data ) {
				$this->add_dataset( $id, $data );
			}

		} else {

			$message = sprintf( 'The %s endpoint has no datasets.', $this->get_endpoint()->get_id() );

			$this->errors->add( 'missing_chart_datasets', $message, $this->get_endpoint() );

		}

		unset( $options['datasets'] );

		$this->options = $options;
	}

	/**
	 * Retrieves parsed options for the chart manifest.
	 *
	 * @since 3.0
	 *
	 * @return array Chart options.
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * Retrieves the manifest datasets.
	 *
	 * @since 3.0
	 *
	 * @return Dataset[] Datasets for this chart if any are defined, otherwise an empty array.
	 */
	public function get_datasets() {
		return $this->datasets;
	}

	/**
	 * Determines whether the current chart manifest contains any datasets.
	 *
	 * @since 3.0
	 *
	 * @return bool True if there are datasets, otherwise false.
	 */
	public function has_datasets() {
		$datasets = $this->get_datasets();

		return ! empty( $datasets );
	}

	/**
	 * Sets the labels property (for pie and doughnut charts).
	 *
	 * @since 3.0
	 *
	 * @param array $labels Array of pie or doughnut chart labels.
	 */
	private function set_labels( $labels ) {
		$this->labels = $labels;
	}

	/**
	 * Retrieves the manifest labels (for pie and doughnut charts).
	 *
	 * @since 3.0
	 */
	public function get_labels() {
		return $this->labels;
	}

	/**
	 * Determines whether the current chart manifest contains any labels (for pie and doughnut charts).
	 *
	 * @since 3.0
	 *
	 * @return bool True if there are labels, otherwise false.
	 */
	public function has_labels() {
		$labels = $this->get_labels();

		return ! empty( $labels );
	}

	/**
	 * Adds a dataset.
	 *
	 * @since 3.0
	 *
	 * @param string $dataset_id ID to associate the dataset with.
	 * @param array  $options    Dataset options.
	 * @return bool True if the dataset was added, otherwise false.
	 */
	public function add_dataset( $dataset_id, $options ) {
		$handler = $this->get_dataset_handler();

		if ( ! empty( $handler ) && class_exists( $handler ) ) {
			/** @var Dataset $dataset */
			$dataset = new $handler( $dataset_id, $this->get_endpoint(), $options );

			if ( ! $dataset->has_errors() ) {

				$this->datasets[ $dataset_id ] = $dataset;


				return true;

			} else {

				$this->errors->add( 'dataset_errors_passthrough', 'Errors have been passed through from dataset parsing.', $dataset->get_errors() );

			}

		}

		return false;
	}

	/**
	 * Retrieves the handler class for the current dataset type.
	 *
	 * @since 3.0
	 *
	 * @return string Dataset handler class.
	 */
	public function get_dataset_handler() {
		$handler = '';

		switch( $this->get_type() ) {

			case 'doughnut':
			case 'pie':
				$handler = 'EDD\Reports\Data\Charts\v2\Pie_Dataset';
				break;

			case 'bar':
				$handler = 'EDD\Reports\Data\Charts\v2\Bar_Dataset';
				break;

			case 'line':
				$handler = 'EDD\Reports\Data\Charts\v2\Line_Dataset';
				break;


		}

		return $handler;
	}


	/**
	 * Renders the manifest in JS form.
	 *
	 * @since 3.0
	 */
	public function render() {
		$config = $this->build_config();

		// Dates.
		$dates      = Reports\get_dates_filter( 'objects' );
		$day_by_day = Reports\get_dates_filter_day_by_day();

		// Adjust end date forward by 1 second to push into the next day (for ChartJS display purposes).
		$dates['end']->addSeconds( 1 );

		$endpoint  = $this->get_endpoint();
		$default   = "edd_reports_graph_{$endpoint->get_id()}";
		$target_el = $endpoint->get_display_arg( 'target', $default );

		// The target ID has to be JS compatible, so no dashes.
		$target_el = str_replace( '-', '_', $target_el );
		?>
		<canvas id="<?php echo esc_attr( $target_el ); ?>"></canvas>

		<script type="application/javascript">
			Chart.defaults.global.pointHitDetectionRadius = 5;

			// Bring in chart config.
			<?php echo esc_js( $target_el ); ?> = <?php echo $config; ?>;

			<?php if ( ! $this->is_pie_manifest() ) : ?>
				// Convert dataset x-axis values to moment() objects.
				<?php echo esc_js( $target_el ); ?>.data.datasets.forEach( function( dataset ) {

					dataset.data.forEach( function( pair, index ) {
						pair.x = moment( pair.x );
					} );

				} );

				// Set min and max moment() values for the x-axis.
				<?php echo esc_js( $target_el ); ?>.options.scales.xAxes.forEach( function( xaxis ) {
					<?php if ( false === $day_by_day ) : ?>
						xaxis.time.unit = 'month';
						console.log( xaxis.time );
					<?php endif; ?>

					xaxis.time.min = moment( '<?php echo esc_js( $dates['start']->toDateTimeString() ); ?>' );
					xaxis.time.max = moment( '<?php echo esc_js( $dates['end']->toDateTimeString() ); ?>' );
				} );

				// Dynamically display currency.
				<?php echo esc_js( $target_el ); ?>.options.tooltips = {
					enabled: false,
					mode: 'index',
					position: 'nearest',
					callbacks: {
						label: function (t, d) {
							<?php
							$options = $this->get_endpoint()->get_options();

							$callback_conditional = '';

							if ( is_array( $options ) && isset( $options['datasets'] ) ) {
								$i = 0;
								foreach ( $options['datasets'] as $dataset ) {
									if ( isset( $dataset['type'] ) && 'currency' === $dataset['type'] ) {
										$callback_conditional .= 't.datasetIndex === ' . $i . ' || ';
									}
									$i ++;
								}
							}

							$callback_conditional = substr( $callback_conditional, 0, - 4 );
							?>
							var yLabel = t.yLabel;

							<?php if ( ! empty( $callback_conditional ) ) : ?>
							if ( <?php echo esc_js( $callback_conditional ); ?> ) {
								yLabel = '<?php echo esc_js( html_entity_decode( edd_currency_symbol( edd_get_currency() ) ) ); ?>' + t.yLabel.toFixed(2);
							}
							<?php endif; ?>

							return d.datasets[t.datasetIndex].label + ': ' + yLabel;
						}
					},

					custom: function (tooltip) {
						// Tooltip element.
						var tooltipEl = document.getElementById( 'edd-chartjs-tooltip' );

						if ( ! tooltipEl ) {
							tooltipEl = document.createElement( 'div' );
							tooltipEl.id = 'edd-chartjs-tooltip';
							tooltipEl.innerHTML = '<table></table>';
							this._chart.canvas.parentNode.appendChild( tooltipEl );
						}

						// Hide if no tooltip.
						if ( tooltip.opacity === 0 ) {
							tooltipEl.style.opacity = 0;
							return;
						}

						// Set caret position.
						tooltipEl.classList.remove( 'above', 'below', 'no-transform' );
						if ( tooltip.yAlign ) {
							tooltipEl.classList.add(tooltip.yAlign );
						} else {
							tooltipEl.classList.add( 'no-transform' );
						}

						function getBody( bodyItem ) {
							return bodyItem.lines;
						}

						// Set Text
						if ( tooltip.body ) {
							var titleLines = tooltip.title || [];
							var bodyLines = tooltip.body.map( getBody );

							var innerHtml = '<thead>';

							titleLines.forEach( function ( title ) {
								innerHtml += '<tr><th>' + title + '</th></tr>';
							});

							innerHtml += '</thead><tbody>';

							bodyLines.forEach( function ( body, i ) {
								var colors = tooltip.labelColors[ i ];
								var style = 'background:' + colors.borderColor;
								style += '; border-color:' + colors.borderColor;
								style += '; border-width: 2px';
								var span = '<span class="edd-chartjs-tooltip-key" style="' + style + '"></span>';
								innerHtml += '<tr><td>' + span + body + '</td></tr>';
							});

							innerHtml += '</tbody>';

							var tableRoot = tooltipEl.querySelector( 'table' );
							tableRoot.innerHTML = innerHtml;
						}

						var positionY = this._chart.canvas.offsetTop;
						var positionX = this._chart.canvas.offsetLeft;

						// Display, position, and set styles for font
						tooltipEl.style.opacity = 1;
						tooltipEl.style.left = positionX + tooltip.caretX + 'px';
						tooltipEl.style.top = positionY + tooltip.caretY + 'px';
						tooltipEl.style.fontFamily = tooltip._bodyFontFamily;
						tooltipEl.style.fontSize = tooltip.bodyFontSize + 'px';
						tooltipEl.style.fontStyle = tooltip._bodyFontStyle;
						tooltipEl.style.padding = tooltip.yPadding + 'px ' + tooltip.xPadding + 'px';
					}
				};

			<?php else : // Pie charts ?>

			// Display percentage amounts with label.
			<?php echo esc_js( $target_el ); ?>.options.tooltips = {
				callbacks: {
					label: function( t, d ) {
						var dataset = d.datasets[ t.datasetIndex ];

						var total = dataset.data.reduce( function( previousValue, currentValue, currentIndex, array ) {
							return previousValue + currentValue;
						} );

						var currentValue = dataset.data[ t.index ];

						var precentage = Math.floor( ( ( currentValue / total ) * 100 ) + 0.5 );

						return d.labels[ t.datasetIndex ] + ': ' + currentValue + ' (' + precentage + '%)';
					}
				}
			};

			<?php endif; ?>

			// Instantiate the chart.
			<?php echo esc_js( $target_el ); ?>_chart = new Chart(
				$( '#<?php echo esc_js( $target_el ); ?>' ),
				<?php echo esc_js( $target_el ); ?>
			);

		</script>
		<?php
	}

	/**
	 * Builds the chart config.
	 *
	 * @since 3.0
	 *
	 * @return string JSON-encoded config object.
	 */
	protected function build_config() {
		$config = new \stdClass();

		$config->type    = $this->get_type();
		$config->data    = $this->get_chart_data();
		$config->options = $this->get_chart_options();

		return json_encode( $config );
	}

	/**
	 * Retrieves the parsed chart datasets as an object.
	 *
	 * @since 3.0
	 *
	 * @return array Parsed chart data.
	 */
	public function get_chart_data() {
		$data = array();

		if ( $this->has_datasets() ) {
			$datasets = $this->get_datasets();

			$data['datasets'] = array();

			foreach ( $datasets as $id => $set ) {
				if ( $set->has_errors() ) {
					continue;
				}

				$data['datasets'][] = $set->get_options();
			}
		}

		if ( $this->is_pie_manifest() ) {
			$data['labels'] = $this->get_labels();
		}

		return $data;
	}

	/**
	 * Retrieves the parsed chart options as an object.
	 *
	 * @since 3.0
	 *
	 * @return array Parsed chart options.
	 */
	public function get_chart_options() {
		if ( $this->is_pie_manifest() ) {
			$defaults = array(
				'responsive' => true,
				'legend'     => array(
					'position' => 'left',
				),
			);
		} else {
			$defaults = array(
				'responsive' => true,
				'hoverMode'  => 'index',
				'stacked'    => false,
				'title'      => array(
					'display' => ! empty( $this->get_endpoint()->get_label() && $this->get_endpoint()->get( 'show_chart_title' ) ),
					'text'    => $this->get_endpoint()->get_label(),
				),
				'scales'     => array(
					'xAxes' => array(
						array(
							'type'     => 'time',
							'display'  => true,
							'ticks'    => array(
								'source' => 'auto',
							),
							'position' => 'bottom',
							'time'     => array(
								'unit'          => 'day',
								'tooltipFormat' => Reports\get_dates_filter_hour_by_hour() ? 'LLL' : 'LL',
							),
						),
					),
					'yAxes' => array(
						array(
							'type'     => 'linear',
							'display'  => true,
							'position' => 'left',
						),
					),
				),
			);
		}

		return array_merge( $defaults, $this->get_options() );
	}

	/**
	 * Determines whether the chart manifest is for a pie or doughnut chart.
	 *
	 * @since 3.0
	 *
	 * @return bool True if the manifest is for a pie or doughnut chart, otherwise false.
	 */
	public function is_pie_manifest() {
		return in_array( $this->get_type(), array( 'pie', 'doughnut' ), true );
	}

	/**
	 * Determines whether the dataset has generated errors during instantiation.
	 *
	 * @since 3.0
	 *
	 * @return bool True if errors have been logged, otherwise false.
	 */
	public function has_errors() {
		if ( method_exists( $this->errors, 'has_errors' ) ) {
			return $this->errors->has_errors();
		} else {
			$errors = $this->errors->get_error_codes();

			return ! empty( $errors );
		}
	}

	/**
	 * Retrieves any logged errors for the dataset.
	 *
	 * @since 3.0
	 *
	 * @return \WP_Error WP_Error object for the current dataset.
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Sets up the WP_Error instance.
	 *
	 * @since 3.0
	 */
	public function setup_error_logger() {
		if ( ! isset( $this->errors ) ) {
			$this->errors = new \WP_Error();
		}
	}

}
