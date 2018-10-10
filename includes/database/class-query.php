<?php
/**
 * Base Database Query Class.
 *
 * @package     EDD
 * @subpackage  Database\Queries
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace EDD\Database;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Base class used for querying custom database tables.
 *
 * @since 3.0
 *
 * @see \EDD\Database\Query::__construct() for accepted arguments.
 *
 * @property string $prefix
 * @property string $table_name
 * @property string $table_alias
 * @property string $table_schema
 * @property string $item_name
 * @property string $item_name_plural
 * @property string $item_shape
 * @property string $cache_group
 * @property string $columns
 * @property string $query_clauses
 * @property string $request_clauses
 * @property \WP_Meta_Query $meta_query
 * @property \WP_Date_Query $date_query
 * @property array $query_vars
 * @property array $query_var_originals
 * @property array $query_var_defaults
 * @property array $items
 * @property int $found_items
 * @property int $max_num_pages
 * @property string $request
 * @property int $last_changed
 * @property mixed $last_error
 * @property string $date_query_sql
 */
class Query extends Base {

	/** Global Properties *****************************************************/

	/**
	 * Global prefix used for tables/hooks/cache-groups/etc...
	 *
	 * @since 3.0
	 * @var   string
	 */
	protected $prefix = 'edd';

	/** Table Properties ******************************************************/

	/**
	 * Name of the database table to query.
	 *
	 * @since 3.0
	 * @var   string
	 */
	protected $table_name = '';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * Keep this short, but descriptive. I.E. "oi" for order items.
	 *
	 * This is used to avoid collisions with JOINs.
	 *
	 * @since 3.0
	 * @var   string
	 */
	protected $table_alias = '';

	/**
	 * Name of class used to setup the database schema
	 *
	 * @since 3.0
	 * @var   string
	 */
	protected $table_schema = '\\EDD\\Database\\Schemas\\Base';

	/** Item ******************************************************************/

	/**
	 * Name for a single item
	 *
	 * Use underscores between words. I.E. "order_item"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @since 3.0
	 * @var   string
	 */
	protected $item_name = '';

	/**
	 * Plural version for a group of items.
	 *
	 * Use underscores between words. I.E. "order_item"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @since 3.0
	 * @var   string
	 */
	protected $item_name_plural = '';

	/**
	 * Name of class used to turn IDs into first-class objects.
	 *
	 * I.E. `\\EDD\\Database\\Row` or `\\EDD\\Database\\Rows\\Customer`
	 *
	 * This is used when looping through return values to guarantee their shape.
	 *
	 * @since 3.0
	 * @var   mixed
	 */
	protected $item_shape = '\\EDD\\Database\\Row';

	/** Cache *****************************************************************/

	/**
	 * Group to cache queries and queried items in.
	 *
	 * Use underscores between words. I.E. "some_items"
	 *
	 * Do not use colons: ":". These are reserved for internal use only.
	 *
	 * @since 3.0
	 * @var   string
	 */
	protected $cache_group = '';

	/** Columns ***************************************************************/

	/**
	 * Array of all database column objects
	 *
	 * @since 3.0
	 * @var   array
	 */
	protected $columns = array();

	/** Clauses ***************************************************************/

	/**
	 * SQL query clauses.
	 *
	 * @since 3.0
	 * @var   array
	 */
	protected $query_clauses = array(
		'select'  => '',
		'from'    => '',
		'where'   => array(),
		'groupby' => '',
		'orderby' => '',
		'limits'  => ''
	);

	/**
	 * Request clauses.
	 *
	 * @since 3.0
	 * @var   array
	 */
	protected $request_clauses = array(
		'select'  => '',
		'from'    => '',
		'where'   => '',
		'groupby' => '',
		'orderby' => '',
		'limits'  => ''
	);

	/**
	 * Meta query container.
	 *
	 * @since 3.0
	 * @var   object|\WP_Meta_Query
	 */
	protected $meta_query = false;

	/**
	 * Date query container.
	 *
	 * @since 3.0
	 * @var   \WP_Date_Query
	 */
	protected $date_query = false;

	/**
	 * Parsed query vars set by the user.
	 *
	 * @since 3.0
	 * @var   array
	 */
	protected $query_vars = array();

	/**
	 * Original query vars set by the user.
	 *
	 * @since 3.0
	 * @var   array
	 */
	protected $query_var_originals = array();

	/**
	 * Default values for query vars.
	 *
	 * @since 3.0
	 * @var   array
	 */
	protected $query_var_defaults = array();

	/** Items *****************************************************************/

	/**
	 * List of items located by the query.
	 *
	 * @since 3.0
	 * @var   array
	 */
	public $items = array();

	/**
	 * The amount of found items for the current query.
	 *
	 * @since 3.0
	 * @var   int
	 */
	protected $found_items = 0;

	/**
	 * The number of pages.
	 *
	 * @since 3.0
	 * @var   int
	 */
	protected $max_num_pages = 0;

	/**
	 * SQL for database query.
	 *
	 * @since 3.0
	 * @var   string
	 */
	protected $request = '';

	/**
	 * The last updated time.
	 *
	 * @since 3.0
	 * @var   int
	 */
	protected $last_changed = 0;

	/**
	 * The last error, if any.
	 *
	 * @since 3.0
	 * @var   mixed
	 */
	protected $last_error = false;

	/**
	 * This private variable only exists to temporarily hold onto the SQL used
	 * in a date query. This is necessary to work around WordPress filters.
	 *
	 * @since 3.0
	 * @var   string
	 */
	private $date_query_sql = '';

	/** Methods ***************************************************************/

	/**
	 * Sets up the item query, based on the query vars passed.
	 *
	 * @since 3.0
	 *
	 * @param string|array $query {
	 *     Optional. Array or query string of item query parameters.
	 *     Default empty.
	 *
	 *     @type string       $fields            Site fields to return. Accepts 'ids' (returns an array of item IDs)
	 *                                           or empty (returns an array of complete item objects). Default empty.
	 *     @type boolean      $count             Whether to return a item count (true) or array of item objects.
	 *                                           Default false.
	 *     @type integer      $number            Limit number of items to retrieve. Use 0 for no limit.
	 *                                           Default 100.
	 *     @type integer      $offset            Number of items to offset the query. Used to build LIMIT clause.
	 *                                           Default 0.
	 *     @type boolean      $no_found_rows     Whether to disable the `SQL_CALC_FOUND_ROWS` query.
	 *                                           Default true.
	 *     @type string|array $orderby           Accepts false, an empty array, or 'none' to disable `ORDER BY` clause.
	 *                                           Default 'id'.
	 *     @type string       $item              How to item retrieved items. Accepts 'ASC', 'DESC'.
	 *                                           Default 'DESC'.
	 *     @type string       $search            Search term(s) to retrieve matching items for.
	 *                                           Default empty.
	 *     @type array        $search_columns    Array of column names to be searched.
	 *                                           Default empty array.
	 *     @type boolean      $update_item_cache Whether to prime the cache for found items.
	 *                                           Default false.
	 *     @type boolean      $update_meta_cache Whether to prime the meta cache for found items.
	 *                                           Default false.
	 * }
	 */
	public function __construct( $query = array() ) {

		// Setup
		$this->set_prefix();
		$this->set_columns();
		$this->set_item_shape();
		$this->set_query_var_defaults();

		// Maybe execute a query if arguments were passed
		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	/**
	 * Queries the database and retrieves items or counts.
	 *
	 * This method is public to allow subclasses to perform JIT manipulation
	 * of the parameters passed into it.
	 *
	 * @since 3.0
	 *
	 * @param string|array $query Array or URL query string of parameters.
	 * @return array|int List of items, or number of items when 'count' is passed as a query var.
	 */
	public function query( $query = array() ) {
		$this->parse_query( $query );

		return $this->get_items();
	}

	/** Private Setters *******************************************************/

	/**
	 * Set the time when items were last changed.
	 *
	 * We set this locally to avoid inconsistencies between method calls.
	 *
	 * @since 3.0
	 */
	private function set_last_changed() {
		$this->last_changed = microtime();
	}

	/**
	 * Prefix table names, cache groups, and other things.
	 *
	 * This is to avoid conflicts with other plugins or themes that might be doing their own things.
	 *
	 * @since 3.0
	 */
	private function set_prefix() {

		// Bail if no prefix
		if ( empty( $this->prefix ) ) {
			return;
		}

		// Add prefixes to class properties
		$this->table_name  = $this->apply_prefix( $this->table_name       );
		$this->table_alias = $this->apply_prefix( $this->table_alias      );
		$this->cache_group = $this->apply_prefix( $this->cache_group, '-' );
	}

	/**
	 * Set columns objects
	 *
	 * @since 3.0
	 */
	private function set_columns() {

		// Bail if no table schema
		if ( ! class_exists( $this->table_schema ) ) {
			return;
		}

		// Invoke a new table schema class
		$schema = new $this->table_schema;

		// Maybe get the column objects
		if ( ! empty( $schema->columns ) ) {
			$this->columns = $schema->columns;
		}
	}

	/**
	 * Set the default item shape if none exists
	 *
	 * @since 3.0
	 */
	private function set_item_shape() {
		if ( empty( $this->item_shape ) || ! class_exists( $this->item_shape ) ) {
			$this->item_shape = 'EDD\\Database\\Row';
		}
	}

	/**
	 * Set default query vars based on columns
	 *
	 * @since 3.0
	 */
	private function set_query_var_defaults() {

		// Default query variables
		$this->query_var_defaults = array(
			'fields'            => '',
			'number'            => 100,
			'offset'            => '',
			'orderby'           => 'id',
			'order'             => 'DESC',
			'groupby'           => '',
			'search'            => '',
			'search_columns'    => array(),
			'count'             => false,
			'meta_query'        => null, // See WP_Meta_Query
			'date_query'        => null, // See WP_Date_Query
			'compare'           => null, // See WP_Meta_Query
			'no_found_rows'     => true,

			// Caching
			'update_item_cache' => true,
			'update_meta_cache' => true
		);

		// Bail if no columns
		if ( empty( $this->columns ) ) {
			return;
		}

		// Direct column names
		$names = wp_list_pluck( $this->columns, 'name' );
		foreach ( $names as $name ) {
			$this->query_var_defaults[ $name ] = '';
		}

		// Possible ins
		$possible_ins = $this->get_columns( array( 'in' => true ), 'and', 'name' );
		foreach ( $possible_ins as $in ) {
			$key = "{$in}__in";
			$this->query_var_defaults[ $key ] = false;
		}

		// Possible not ins
		$possible_not_ins = $this->get_columns( array( 'not_in' => true ), 'and', 'name' );
		foreach ( $possible_not_ins as $in ) {
			$key = "{$in}__not_in";
			$this->query_var_defaults[ $key ] = false;
		}

		// Possible dates
		$possible_dates = $this->get_columns( array( 'date_query' => true ), 'and', 'name' );
		foreach ( $possible_dates as $date ) {
			$key = "{$date}_query";
			$this->query_var_defaults[ $key ] = false;
		}
	}

	/**
	 * Set the request clauses
	 *
	 * @since 3.0
	 *
	 * @param array $clauses
	 */
	private function set_request_clauses( $clauses = array() ) {

		// Found rows
		$found_rows = empty( $this->query_vars['no_found_rows'] )
			? 'SQL_CALC_FOUND_ROWS'
			: '';

		// Fields
		$fields    = ! empty( $clauses['fields'] )
			? $clauses['fields']
			: '';

		// Join
		$join      = ! empty( $clauses['join'] )
			? $clauses['join']
			: '';

		// Where
		$where     = ! empty( $clauses['where'] )
			? "WHERE {$clauses['where']}"
			: '';

		// Group by
		$groupby   = ! empty( $clauses['groupby'] )
			? "GROUP BY {$clauses['groupby']}"
			: '';

		// Order by
		$orderby   = ! empty( $clauses['orderby'] )
			? "ORDER BY {$clauses['orderby']}"
			: '';

		// Limits
		$limits   = ! empty( $clauses['limits']  )
			? $clauses['limits']
			: '';

		// Select & From
		$table  = $this->get_table_name();
		$select = "SELECT {$found_rows} {$fields}";
		$from   = "FROM {$table} {$this->table_alias} {$join}";

		// Put query into clauses array
		$this->request_clauses['select']  = $select;
		$this->request_clauses['from']    = $from;
		$this->request_clauses['where']   = $where;
		$this->request_clauses['groupby'] = $groupby;
		$this->request_clauses['orderby'] = $orderby;
		$this->request_clauses['limits']  = $limits;
	}

	/**
	 * Set the request
	 *
	 * @since 3.0
	 */
	private function set_request() {
		$filtered      = array_filter( $this->request_clauses );
		$clauses       = array_map( 'trim', $filtered );
		$this->request = implode( ' ', $clauses );
	}

	/**
	 * Set items by mapping them through the single item callback.
	 *
	 * @since 3.0
	 * @param array $item_ids
	 */
	private function set_items( $item_ids = array() ) {

		// Bail if counting, to avoid shaping items
		if ( ! empty( $this->query_vars['count'] ) ) {
			$this->items = $item_ids;
			return;
		}

		// Cast to integers
		$item_ids = array_map( 'intval', $item_ids );

		// Prime item caches
		$this->prime_item_caches( $item_ids );

		// Shape the items
		$this->items = $this->shape_items( $item_ids );
	}

	/**
	 * Populates found_items and max_num_pages properties for the current query
	 * if the limit clause was used.
	 *
	 * @since 3.0
	 *
	 * @param  array $item_ids Optional array of item IDs
	 */
	private function set_found_items( $item_ids = array() ) {

		// Items were not found
		if ( empty( $item_ids ) ) {
			return;
		}

		// Default to number of item IDs
		$this->found_items = count( (array) $item_ids );

		// Count query
		if ( ! empty( $this->query_vars['count'] ) ) {

			// Not grouped
			if ( is_numeric( $item_ids ) && empty( $this->query_vars['groupby'] ) ) {
				$this->found_items = intval( $item_ids );
			}

		// Not a count query
		} elseif ( is_array( $item_ids ) && ( ! empty( $this->query_vars['number'] ) && empty( $this->query_vars['no_found_rows'] ) ) ) {

			/**
			 * Filters the query used to retrieve found item count.
			 *
			 * @since 3.0
			 *
			 * @param string $found_items_query SQL query. Default 'SELECT FOUND_ROWS()'.
			 * @param object $item_query        The object instance.
			 */
			$found_items_query = (string) apply_filters_ref_array( $this->apply_prefix( "found_{$this->item_name_plural}_query" ), array( 'SELECT FOUND_ROWS()', &$this ) );

			// Maybe query for found items
			if ( ! empty( $found_items_query ) ) {
				$this->found_items = (int) $this->get_db()->get_var( $found_items_query );
			}
		}
	}

	/** Private Getters *******************************************************/

	/**
	 * Return the global database interface
	 *
	 * @since 3.0
	 *
	 * @return wpdb|object
	 */
	private static function get_db() {
		return isset( $GLOBALS['wpdb'] )
			? $GLOBALS['wpdb']
			: new stdClass();
	}

	/**
	 * Pass-through method to return a new WP_Meta_Query object
	 *
	 * @since 3.0
	 *
	 * @param array $args See WP_Date_Query
	 * @return \WP_Meta_Query
	 */
	private function get_meta_query( $args = array() ) {
		return new \WP_Meta_Query( $args );
	}

	/**
	 * Pass-through method to return a new WP_Date_Query object
	 *
	 * @since 3.0
	 *
	 * @param array  $args   See WP_Date_Query
	 * @param string $column Column to run date query on. Default `date_created`
	 *
	 * @return \WP_Date_Query
	 */
	private function get_date_query( $args = array(), $column = 'date_created' ) {

		// Filter valid date columns for these columns
		add_filter( 'date_query_valid_columns', array( $this, '__filter_valid_date_columns' ), 2 );

		$date_query = new \WP_Date_Query( $args, $column );
		$table      = $this->get_table_name();

		$date_query->column = "{$table}.{$column}";
		$date_query->validate_column( $column );
		$this->date_query_sql = $date_query->get_sql();

		// Remove all valid columns filters
		remove_filter( 'date_query_valid_columns', array( $this, '__filter_valid_date_columns' ), 2 );

		// Return the date
		return $date_query;
	}

	/**
	 * This public method should not be called directly ever.
	 *
	 * It only exists to hack around a WordPress core issue with WP_Date_Query
	 * column stubbornness.
	 *
	 * @since 3.0
	 *
	 * @access private
	 * @param array $columns
	 * @return array
	 */
	public function __filter_valid_date_columns( $columns = array() ) {
		$columns = $this->get_columns( array( 'date_query' => true ), 'and', 'name' );
		return $columns;
	}

	/**
	 * Return the current time as a UTC timestamp
	 *
	 * This is used by add_item() and update_item()
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	private function get_current_time() {
		return gmdate( "Y-m-d\TH:i:s\Z" );
	}

	/**
	 * Return the literal table name (with prefix) from $wpdb
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	private function get_table_name() {
		return $this->get_db()->{$this->table_name};
	}

	/**
	 * Return array of column names
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	private function get_column_names() {
		return array_flip( $this->get_columns( array(), 'and', 'name' ) );
	}

	/**
	 * Return the primary database column name
	 *
	 * @since 3.0
	 *
	 * @return string Default "id", Primary column name if not empty
	 */
	private function get_primary_column_name() {
		return $this->get_column_field( array( 'primary' => true ), 'name', 'id' );
	}

	/**
	 * Get a column from an array of arguments
	 *
	 * @since 3.0
	 *
	 * @return mixed \EDD\Database\Schemas\Column object, or false
	 */
	private function get_column_field( $args = array(), $field = '', $default = false ) {

		// Get column
		$column = $this->get_column_by( $args );

		// Return field, or default
		return isset( $column->{$field} )
			? $column->{$field}
			: $default;
	}

	/**
	 * Get a column from an array of arguments
	 *
	 * @since 3.0
	 *
	 * @return mixed \EDD\Database\Schemas\Column object, or false
	 */
	private function get_column_by( $args = array() ) {

		// Filter columns
		$filter = $this->get_columns( $args );

		// Return column or false
		return ! empty( $filter )
			? reset( $filter )
			: false;
	}

	/**
	 * Get columns from an array of arguments
	 *
	 * @since 3.0
	 */
	private function get_columns( $args = array(), $operator = 'and', $field = false ) {

		// Filter columns
		$filter = wp_filter_object_list( $this->columns, $args, $operator, $field );

		// Return column or false
		return ! empty( $filter )
			? array_values( $filter )
			: array();
	}

	/**
	 * Get a single database row by any column and value, skipping cache.
	 *
	 * @since 3.0
	 *
	 * @param string $column_name  Name of database column
	 * @param string $column_value Value to query for
	 * @return mixed False if empty/error, Object if successful
	 */
	private function get_item_raw( $column_name = '', $column_value = '' ) {

		// Bail if no name or value
		if ( empty( $column_name ) || empty( $column_value ) ) {
			return false;
		}

		// Bail if values aren't query'able
		if ( ! is_string( $column_name ) || ! is_scalar( $column_value ) ) {
			return false;
		}

		// Query database for row
		$pattern = $this->get_column_field( array( 'name' => $column_name ), 'pattern', '%s' );
		$table   = $this->get_table_name();
		$select  = $this->get_db()->prepare( "SELECT * FROM {$table} WHERE {$column_name} = {$pattern}", $column_value );
		$result  = $this->get_db()->get_row( $select );

		// Bail on failure
		if ( $this->failed( $result ) ) {
			return false;
		}

		// Return row
		return $result;
	}

	/**
	 * Retrieves a list of items matching the query vars.
	 *
	 * @since 3.0
	 *
	 * @return array|int List of items, or number of items when 'count' is passed as a query var.
	 */
	private function get_items() {

		/**
		 * Fires before object items are retrieved.
		 *
		 * @since 3.0
		 *
		 * @param \EDD\Database\Query &$this Current instance of \EDD\Database\Query, passed by reference.
		 */
		do_action_ref_array( $this->apply_prefix( "pre_get_{$this->item_name_plural}" ), array( &$this ) );

		// Never limit, never update item/meta caches when counting
		if ( ! empty( $this->query_vars['count'] ) ) {
			$this->query_vars['number']            = false;
			$this->query_vars['no_found_rows']     = true;
			$this->query_vars['update_item_cache'] = false;
			$this->query_vars['update_meta_cache'] = false;
		}

		// Check the cache
		$cache_key   = $this->get_cache_key();
		$cache_value = $this->cache_get( $cache_key, $this->cache_group );

		// No cache value
		if ( false === $cache_value ) {
			$item_ids = $this->get_item_ids();

			// Set the number of found items
			$this->set_found_items( $item_ids );

			// Format the cached value
			$cache_value = array(
				'item_ids'    => $item_ids,
				'found_items' => intval( $this->found_items ),
			);

			// Add value to the cache
			$this->cache_add( $cache_key, $cache_value, $this->cache_group );

		// Value exists in cache
		} else {
			$item_ids          = $cache_value['item_ids'];
			$this->found_items = intval( $cache_value['found_items'] );
		}

		// Pagination
		if ( ! empty( $this->found_items ) && ! empty( $this->query_vars['number'] ) ) {
			$this->max_num_pages = ceil( $this->found_items / $this->query_vars['number'] );
		}

		// Cast to int if not grouping counts
		if ( ! empty( $this->query_vars['count'] ) && empty( $this->query_vars['groupby'] ) ) {
			$item_ids = intval( $item_ids );
		}

		// Set items from IDs
		$this->set_items( $item_ids );

		// Return array of items
		return $this->items;
	}

	/**
	 * Used internally to get a list of item IDs matching the query vars.
	 *
	 * @since 3.0
	 *
	 * @return int|array A single count of item IDs if a count query. An array of item IDs if a full query.
	 */
	private function get_item_ids() {

		// Setup primary column, and parse the where clause
		$this->parse_where();

		// Order & Order By
		$order   = $this->parse_order( $this->query_vars['order'] );
		$orderby = $this->get_order_by( $order );

		// Limit & Offset
		$limit   = absint( $this->query_vars['number'] );
		$offset  = absint( $this->query_vars['offset'] );

		// Limits
		if ( ! empty( $limit ) ) {
			$limits = ! empty( $offset )
				? "LIMIT {$offset}, {$limit}"
				: "LIMIT {$limit}";
		} else {
			$limits = '';
		}

		// Where & Join
		$where = implode( ' AND ', $this->query_clauses['where'] );
		$join  = $this->query_clauses['join'];

		// Group by
		$groupby = $this->parse_groupby( $this->query_vars['groupby'] );

		// Fields
		$fields  = $this->parse_fields( $this->query_vars['fields'] );

		// Setup the query array (compact() is too opaque here)
		$query = array(
			'fields'  => $fields,
			'join'    => $join,
			'where'   => $where,
			'orderby' => $orderby,
			'limits'  => $limits,
			'groupby' => $groupby
		);

		/**
		 * Filters the item query clauses.
		 *
		 * @since 3.0
		 *
		 * @param array  $pieces A compacted array of item query clauses.
		 * @param object &$this  Current instance passed by reference.
		 */
		$clauses = (array) apply_filters_ref_array( $this->apply_prefix( "{$this->item_name_plural}_query_clauses" ), array( $query, &$this ) );

		// Setup request
		$this->set_request_clauses( $clauses );
		$this->set_request();

		// Return count
		if ( ! empty( $this->query_vars['count'] ) ) {
			return empty( $this->query_vars['groupby'] )
				? $this->get_db()->get_var( $this->request )
				: $this->get_db()->get_results( $this->request, ARRAY_A );
		}

		// Get IDs
		$item_ids = $this->get_db()->get_col( $this->request );

		// Return parsed IDs
		return wp_parse_id_list( $item_ids );
	}

	/**
	 * Get the ORDERBY clause.
	 *
	 * @since 3.0
	 *
	 * @param string $order
	 * @return string
	 */
	private function get_order_by( $order = '' ) {

		// Default orderby primary column
		$orderby = "{$this->parse_orderby()} {$order}";

		// Disable ORDER BY if counting, or: 'none', an empty array, or false.
		if ( ! empty( $this->query_vars['count'] ) || in_array( $this->query_vars['orderby'], array( 'none', array(), false ), true ) ) {
			$orderby = '';

		// Ordering by something, so figure it out
		} elseif ( ! empty( $this->query_vars['orderby'] ) ) {

			// Array of keys, or comma separated
			$ordersby = is_array( $this->query_vars['orderby'] )
				? $this->query_vars['orderby']
				: preg_split( '/[,\s]/', $this->query_vars['orderby'] );

			$orderby_array = array();
			$possible_ins  = $this->get_columns( array( 'in'       => true ), 'and', 'name' );
			$sortables     = $this->get_columns( array( 'sortable' => true ), 'and', 'name' );

			// Loop through possible order by's
			foreach ( $ordersby as $_key => $_value ) {

				// Skip if empty
				if ( empty( $_value ) ) {
					continue;
				}

				// Key is numeric
				if ( is_int( $_key ) ) {
					$_orderby = $_value;
					$_item    = $order;

				// Key is string
				} else {
					$_orderby = $_key;
					$_item    = $_value;
				}

				// Skip if not sortable
				if ( ! in_array( $_value, $sortables, true ) ) {
					continue;
				}

				// Parse orderby
				$parsed = $this->parse_orderby( $_orderby );

				// Skip if empty
				if ( empty( $parsed ) ) {
					continue;
				}

				// Set if __in
				if ( in_array( $_orderby, $possible_ins, true ) ) {
					$orderby_array[] = "{$parsed} {$order}";
					continue;
				}

				// Append parsed orderby to array
				$orderby_array[] = $parsed . ' ' . $this->parse_order( $_item );
			}

			// Only set if valid orderby
			if ( ! empty( $orderby_array ) ) {
				$orderby = implode( ', ', $orderby_array );
			}
		}

		// Return parsed orderby
		return $orderby;
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns.
	 *
	 * @since 3.0
	 *
	 * @param string $string  Search string.
	 * @param array  $columns Columns to search.
	 * @return string Search SQL.
	 */
	private function get_search_sql( $string = '', $columns = array() ) {

		// Array or String
		$like = ( false !== strpos( $string, '*' ) )
			? '%' . implode( '%', array_map( array( $this->get_db(), 'esc_like' ), explode( '*', $string ) ) ) . '%'
			: '%' . $this->get_db()->esc_like( $string ) . '%';

		// Default array
		$searches = array();

		// Build search SQL
		foreach ( $columns as $column ) {
			$searches[] = $this->get_db()->prepare( "{$column} LIKE %s", $like );
		}

		// Return the clause
		return '(' . implode( ' OR ', $searches ) . ')';
	}

	/** Private Parsers *******************************************************/

	/**
	 * Parses arguments passed to the item query with default query parameters.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Database\Query::__construct()
	 *
	 * @param string|array $query Array or string of \EDD\Database\Query arguments. See \EDD\Database\Query::__construct().
	 */
	private function parse_query( $query = array() ) {

		// Setup the query_vars_original var
		$this->query_var_originals = wp_parse_args( $query );

		// Setup the query_vars parsed var
		$this->query_vars = wp_parse_args(
			$this->query_var_originals,
			$this->query_var_defaults
		);

		/**
		 * Fires after the item query vars have been parsed.
		 *
		 * @since 3.0
		 *
		 * @param \EDD\Database\Query &$this The \EDD\Database\Query instance (passed by reference).
		 */
		do_action_ref_array( $this->apply_prefix( "parse_{$this->item_name_plural}_query" ), array( &$this ) );
	}

	/**
	 * Parse the where clauses for all known columns
	 *
	 * @since 3.0
	 */
	private function parse_where() {

		// Defaults
		$where = $searchable = $date_query = array();
		$join  = '';
		$and   = '/^\s*AND\s*/';

		// Loop through columns
		foreach ( $this->columns as $column ) {

			// Maybe add name to searchable array
			if ( true === $column->searchable ) {
				$searchable[] = $column->name;
			}

			// Literal column comparison
			if ( ! empty( $this->query_vars[ $column->name ] ) ) {

				// Array (unprepared)
				if ( is_array( $this->query_vars[ $column->name ] ) ) {
					$where_id  = "'" . implode( "', '", $this->get_db()->_escape( $this->query_vars[ $column->name ] ) ) . "'";
					$statement = "{$this->table_alias}.{$column->name} IN ({$where_id})";

					// Add to where array
					$where[ $column->name ] = $statement;

				// Numeric/String/Float (prepared)
				} else {
					$pattern   = $this->get_column_field( array( 'name' => $column->name ), 'pattern', '%s' );
					$where_id  = $this->query_vars[ $column->name ];
					$statement = "{$this->table_alias}.{$column->name} = {$pattern}";

					// Add to where array
					$where[ $column->name ] = $this->get_db()->prepare( $statement, $where_id );
				}
			}

			// __in
			if ( true === $column->in ) {
				$where_id = "{$column->name}__in";

				// Parse item for an IN clause.
				if ( isset( $this->query_vars[ $where_id ] ) && is_array( $this->query_vars[ $where_id ] ) ) {

					// Convert single item arrays to literal column comparisons
					if ( 1 === count( $this->query_vars[ $where_id ] ) ) {
						$column_value = reset( $this->query_vars[ $where_id ] );
						$statement    = "{$this->table_alias}.{$column->name} = %s";

						$where[ $column->name ] = $this->get_db()->prepare( $statement, $column_value );

					// Implode
					} else {
						$where[ $where_id ] = "{$this->table_alias}.{$column->name} IN ( '" . implode( "', '", $this->get_db()->_escape( $this->query_vars[ $where_id ] ) ) . "' )";
					}
				}
			}

			// __not_in
			if ( true === $column->not_in ) {
				$where_id = "{$column->name}__not_in";

				// Parse item for a NOT IN clause.
				if ( isset( $this->query_vars[ $where_id ] ) && is_array( $this->query_vars[ $where_id ] ) ) {

					// Convert single item arrays to literal column comparisons
					if ( 1 === count( $this->query_vars[ $where_id ] ) ) {
						$column_value = reset( $this->query_vars[ $where_id ] );
						$statement    = "{$this->table_alias}.{$column->name} != %s";

						$where[ $column->name ] = $this->get_db()->prepare( $statement, $column_value );

					// Implode
					} else {
						$where[ $where_id ] = "{$this->table_alias}.{$column->name} NOT IN ( '" . implode( "', '", $this->get_db()->_escape( $this->query_vars[ $where_id ] ) ) . "' )";
					}
				}
			}

			// date_query
			if ( true === $column->date_query ) {
				$where_id    = "{$column->name}_query";
				$column_date = $this->query_vars[ $where_id ];

				// Parse item
				if ( ! empty( $column_date ) ) {

					// Default arguments
					$defaults = array(
						'column'    => "{$this->table_alias}.{$column->name}",
						'before'    => $column_date,
						'inclusive' => true
					);

					// Default date query
					if ( is_string( $column_date ) ) {
						$date_query[] = $defaults;
					} elseif ( is_array( $column_date ) ) {

						// Maybe auto-fill column
						if ( ! isset( $column_date['column'] ) ) {
							$column_date['column'] = $defaults['column'];
						}

						// Add clause to date query
						$date_query[] = $column_date;
					}
				}
			}
		}

		// Maybe search if columns are searchable
		if ( ! empty( $searchable ) && strlen( $this->query_vars['search'] ) ) {
			$search_columns = array();

			// Intersect against known searchable columns
			if ( ! empty( $this->query_vars['search_columns'] ) ) {
				$search_columns = array_intersect(
					$this->query_vars['search_columns'],
					$searchable
				);
			}

			// Default to all searchable columns
			if ( empty( $search_columns ) ) {
				$search_columns = $searchable;
			}

			/**
			 * Filters the columns to search in a \EDD\Database\Query search.
			 *
			 * @since 3.0
			 *
			 * @param array  $search_columns Array of column names to be searched.
			 * @param string $search         Text being searched.
			 * @param object $this           The current \EDD\Database\Query instance.
			 */
			$search_columns = (array) apply_filters( $this->apply_prefix( "{$this->item_name_plural}_search_columns" ), $search_columns, $this->query_vars['search'], $this );

			// Add search query clause
			$where['search'] = $this->get_search_sql( $this->query_vars['search'], $search_columns );
		}

		// Maybe perform a meta-query
		$meta_query = $this->query_vars['meta_query'];
		if ( ! empty( $meta_query ) && is_array( $meta_query ) ) {
			$this->meta_query = $this->get_meta_query( $meta_query );
			$table            = $this->apply_prefix( $this->item_name );
			$clauses          = $this->meta_query->get_sql( $table, $this->table_alias, $this->get_primary_column_name(), $this );

			// Not all objects have meta, so make sure this one exists
			if ( false !== $clauses ) {

				// Set join
				$join = $clauses['join'];

				// Remove " AND " from meta_query query where clause
				$where['meta_query'] = preg_replace( $and, '', $clauses['where'] );
			}
		}

		// Maybe perform a compare.
		$compare = $this->query_vars['compare'];
		if ( ! empty( $compare ) && is_array( $compare ) ) {
			$query   = new Queries\Compare( $compare );
			$table   = $this->apply_prefix( $this->item_name );
			$clauses = $query->get_sql( $table, $this->table_alias, $this->get_primary_column_name(), $this );

			if ( false !== $clauses ) {

				// Remove " AND " from query where clause.
				$where['compare'] = preg_replace( $and, '', $clauses['where'] );
			}
		}

		// Only do a date query with an array
		$date_query = ! empty( $date_query )
			? $date_query
			: $this->query_vars['date_query'];

		// Maybe perform a date-query
		if ( ! empty( $date_query ) && is_array( $date_query ) ) {
			$this->date_query    = $this->get_date_query( $date_query );
			$where['date_query'] = preg_replace( $and, '', $this->date_query_sql );
		}

		// Set where and join clauses
		$this->query_clauses['where'] = $where;
		$this->query_clauses['join']  = $join;
	}

	/**
	 * Parse which fields to query for
	 *
	 * @since 3.0
	 *
	 * @param string $fields
	 * @param bool   $alias
	 * @return string
	 */
	private function parse_fields( $fields = '', $alias = true ) {

		// Default return value
		$primary = $this->get_primary_column_name();
		$retval  = ( true === $alias )
			? "{$this->table_alias}.{$primary}"
			: $primary;

		// No fields
		if ( empty( $fields ) && ! empty( $this->query_vars['count'] ) ) {

			// Possible fields to group by
			$groupby_names = $this->parse_groupby( $this->query_vars['groupby'], false );
			$groupby_names = ! empty( $groupby_names )
				? "{$groupby_names}"
				: '';

			// Group by or total count
			$retval = ! empty( $groupby_names )
				? "{$groupby_names}, COUNT(*) as count"
				: 'COUNT(*)';
		}

		// Return fields (or COUNT)
		return $retval;
	}

	/**
	 * Parses and sanitizes the 'groupby' keys passed into the item query
	 *
	 * @since 3.0
	 *
	 * @param string $groupby
	 * @return string
	 */
	private function parse_groupby( $groupby = '', $alias = true ) {

		// Bail if empty
		if ( empty( $groupby ) ) {
			return '';
		}

		// Sanitize keys
		$groupby = (array) array_map( 'sanitize_key', (array) $groupby );

		// Orderby is a literal column name
		$columns   = array_flip( $this->get_column_names() );
		$intersect = array_intersect( $columns, $groupby );

		// Bail if invalid column
		if ( empty( $intersect ) ) {
			return '';
		}

		// Default return value
		$retval = array();

		// Prepend table alias to key
		foreach ( $intersect as $key ) {
			$retval[] = ( true === $alias )
				? "{$this->table_alias}.{$key}"
				: $key;
		}

		// Separate sanitized columns
		return implode( ',', array_values( $retval ) );
	}

	/**
	 * Parses and sanitizes 'orderby' keys passed to the item query.
	 *
	 * @since 3.0
	 *
	 * @param string $orderby Field for the items to be ordered by.
	 * @return string|false Value to used in the ORDER clause. False otherwise.
	 */
	private function parse_orderby( $orderby = 'id' ) {

		// Default value
		$parsed = "{$this->table_alias}.{$this->get_primary_column_name()}";

		// __in
		if ( false !== strstr( $orderby, '__in' ) ) {
			$column_name = str_replace( '__in', '', $orderby );
			$column      = $this->get_column_by( array( 'name' => $column_name ) );
			$item_in     = $column->is_numeric()
				? implode( ',', array_map( 'absint', $this->query_vars[ $orderby ] ) )
				: implode( ',', $this->query_vars[ $orderby ] );

			$parsed = "FIELD( {$this->table_alias}.{$column->name}, {$item_in} )";

		// Specific column
		} else {

			// Orderby is a literal, sortable column name
			$sortables = $this->get_columns( array( 'sortable' => true ), 'and', 'name' );
			if ( in_array( $orderby, $sortables, true ) ) {
				$parsed = "{$this->table_alias}.{$orderby}";
			}
		}

		// Return parsed value
		return $parsed;
	}

	/**
	 * Parses an 'order' query variable and cast it to 'ASC' or 'DESC' as necessary.
	 *
	 * @since 3.0
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	private function parse_order( $order  = '' ) {

		// Bail if malformed
		if ( empty( $order ) || ! is_string( $order ) ) {
			return `DESC`;
		}

		// Ascending or Descending
		return ( 'ASC' === strtoupper( $order ) )
			? 'ASC'
			: 'DESC';
	}

	/** Private Shapers *******************************************************/

	/**
	 * Maybe append the prefix to string.
	 *
	 * @since 3.0
	 *
	 * @param string $string
	 * @param string $sep
	 * @return string
	 */
	private function apply_prefix( $string = '', $sep = '_' ) {
		return ! empty( $this->prefix )
			? "{$this->prefix}{$sep}{$string}"
			: $string;
	}

	/**
	 * Shape items into their most relevant objects.
	 *
	 * This will try to use item_shape, but will fallback to a private
	 * method for querying and caching items.
	 *
	 * If using the `fields` parameter, results will have unique shapes based on
	 * exactly what was requested.
	 *
	 * @since 3.0
	 *
	 * @param array $items
	 * @return array
	 */
	private function shape_items( $items = array() ) {

		// Force to stdClass if querying for fields
		if ( ! empty( $this->query_vars['fields'] ) ) {
			$this->item_shape = 'stdClass';
		}

		// Default return value
		$retval = array();

		// Use foreach because it's faster than array_map()
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				$retval[] = $this->get_item( $item );
			}
		}

		/**
		 * Filters the object query results.
		 *
		 * Looks like `edd_get_customers`
		 *
		 * @since 3.0
		 *
		 * @param array  $retval An array of items.
		 * @param object &$this  Current instance of \EDD\Database\Query, passed by reference.
		 */
		$retval = (array) apply_filters_ref_array( $this->apply_prefix( "the_{$this->item_name_plural}" ), array( $retval, &$this ) );

		// Return filtered results
		return ! empty( $this->query_vars['fields'] )
			? $this->get_item_fields( $retval )
			: $retval;
	}

	/**
	 * Get specific item fields based on query_vars['fields'].
	 *
	 * @since 3.0
	 *
	 * @param array $items
	 * @return array
	 */
	private function get_item_fields( $items = array() ) {

		// Get the primary column
		$primary = $this->get_primary_column_name();
		$fields  = $this->query_vars['fields'];

		// Strings need to be single columns
		if ( is_string( $fields ) ) {
			$field = sanitize_key( $fields );
			$items = ( 'ids' === $fields )
				? wp_list_pluck( $items, $primary )
				: wp_list_pluck( $items, $field, $primary );

		// Arrays could be anything
		} elseif ( is_array( $fields ) ) {
			$new_items = array();
			$fields    = array_flip( $fields );

			// Loop through items and pluck out the fields
			foreach ( $items as $item_id => $item ) {
				$new_items[ $item_id ] = (object) array_intersect_key( (array) $item, $fields );
			}

			// Set the items and unset the new items
			$items = $new_items;
			unset( $new_items );
		}

		// Return the item, possibly reduced
		return $items;
	}

	/**
	 * Shape an item ID from an object, array, or numeric value
	 *
	 * @since 3.0
	 *
	 * @param  mixed $item
	 * @return int
	 */
	private function shape_item_id( $item = 0 ) {
		$retval  = 0;
		$primary = $this->get_primary_column_name();

		// Item ID
		if ( is_numeric( $item ) ) {
			$retval = $item;
		} elseif ( is_object( $item ) && isset( $item->{$primary} ) ) {
			$retval = $item->{$primary};
		} elseif ( is_array( $item ) && isset( $item[ $primary ] ) ) {
			$retval = $item[ $primary ];
		}

		// Return the item ID
		return absint( $retval );
	}

	/** Queries ***************************************************************/

	/**
	 * Get a single database row by the primary column ID, possibly from cache
	 *
	 * @since 3.0
	 *
	 * @param int $item_id
	 * @return mixed False if empty/error, Object if successful
	 */
	public function get_item( $item_id = 0 ) {

		// Bail if no item to get by
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) ) {
			return false;
		}

		// Get item by ID
		return $this->get_item_by( $this->get_primary_column_name(), $item_id );
	}

	/**
	 * Get a single database row by any column and value, possibly from cache.
	 *
	 * @since 3.0
	 *
	 * @param string $column_name  Name of database column
	 * @param string $column_value Value to query for
	 * @return mixed False if empty/error, Object if successful
	 */
	public function get_item_by( $column_name = '', $column_value = '' ) {

		// Default return value
		$retval = false;

		// Bail if no key or value
		if ( empty( $column_name ) || empty( $column_value ) ) {
			return $retval;
		}

		// Get column names
		$columns = $this->get_column_names();

		// Bail if column does not exist
		if ( ! isset( $columns[ $column_name ] ) ) {
			return $retval;
		}

		// Cache groups
		$groups = $this->get_cache_groups();

		// Check cache
		if ( ! empty( $groups[ $column_name ] ) ) {
			$retval = $this->cache_get( $column_value, $groups[ $column_name ] );
		}

		// Item not cached
		if ( false === $retval ) {

			// Try to get item directly from DB
			$retval = $this->get_item_raw( $column_name, $column_value );

			// Bail on failure
			if ( $this->failed( $retval ) ) {
				return false;
			}

			// Cache
			$this->update_item_cache( $retval );
		}

		// Reduce the item
		$retval = $this->reduce_item( 'select', $retval );

		// Return result
		return $this->shape_item( $retval );
	}

	/**
	 * Add an item to the database
	 *
	 * @since 3.0
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function add_item( $data = array() ) {

		// Get primary column
		$primary = $this->get_primary_column_name();

		// Bail if data to add includes the primary column
		if ( isset( $data[ $primary ] ) ) {
			return false;
		}

		// Get default values for item (from columns)
		$item = $this->default_item();

		// Unset the primary key value from defaults
		unset( $item[ $primary ] );

		// Cut out non-keys for meta
		$columns = $this->get_column_names();
		$data    = array_merge( $item, $data );
		$meta    = array_diff_key( $data, $columns );
		$save    = array_intersect_key( $data, $columns );

		// Get the current time (maybe used by created/modified)
		$time = $this->get_current_time();

		// If date-created exists, but is empty or default, use the current time
		$created = $this->get_column_by( array( 'created' => true ) );
		if ( ! empty( $created ) && ( empty( $save[ $created->name ] ) || ( $save[ $created->name ] === $created->default ) ) ) {
			$save[ $created->name ] = $time;
		}

		// If date-modified exists, but is empty or default, use the current time
		$modified = $this->get_column_by( array( 'modified' => true ) );
		if ( ! empty( $modified ) && ( empty( $save[ $modified->name ] ) || ( $save[ $modified->name ] === $modified->default ) ) ) {
			$save[ $modified->name ] = $time;
		}

		// Try to add
		$table  = $this->get_table_name();
		$reduce = $this->reduce_item( 'insert', $save );
		$save   = $this->validate_item( $reduce );
		$result = ! empty( $save )
			? $this->get_db()->insert( $table, $save )
			: false;

		// Bail on failure
		if ( $this->failed( $result ) ) {
			return false;
		}

		// Get the new item ID
		$item_id = $this->get_db()->insert_id;

		// Maybe save meta keys
		if ( ! empty( $meta ) ) {
			$this->save_extra_item_meta( $item_id, $meta );
		}

		// Use get item to prime caches
		$this->update_item_cache( $item_id );

		// Transition item data
		$this->transition_item( $save, $item_id );

		// Return result
		return $item_id;
	}

	/**
	 * Update an item in the database
	 *
	 * @since 3.0
	 *
	 * @param int $item_id
	 * @param array $data
	 * @return boolean
	 */
	public function update_item( $item_id = 0, $data = array() ) {

		// Bail if no item ID
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) ) {
			return false;
		}

		// Get primary column
		$primary = $this->get_primary_column_name();

		// Get item to update (from database, not cache)
		$item    = $this->get_item_raw( $primary, $item_id );

		// Bail if item does not exist to update
		if ( empty( $item ) ) {
			return false;
		}

		// Cast as an array for easier manipulation
		$item = (array) $item;

		// Unset the primary key from data to parse
		unset( $data[ $primary ] );

		// Splice new data into item, and cut out non-keys for meta
		$columns = $this->get_column_names();
		$data    = array_merge( $item, $data );
		$meta    = array_diff_key( $data, $columns );
		$save    = array_intersect_key( $data, $columns );

		// Bail if no change
		if ( (array) $save === (array) $item ) {
			return true;
		}

		// Unset the primary key from data to save
		unset( $save[ $primary ] );

		// If date-modified is empty, use the current time
		$modified = $this->get_column_by( array( 'modified' => true ) );
		if ( ! empty( $modified ) ) {
			$save[ $modified->name ] = $this->get_current_time();
		}

		// Try to update
		$where  = array( $primary => $item_id );
		$table  = $this->get_table_name();
		$reduce = $this->reduce_item( 'update', $save );
		$save   = $this->validate_item( $reduce );
		$result = ! empty( $save )
			? $this->get_db()->update( $table, $save, $where )
			: false;

		// Bail on failure
		if ( $this->failed( $result ) ) {
			return false;
		}

		// Maybe save meta keys
		if ( ! empty( $meta ) ) {
			$this->save_extra_item_meta( $item_id, $meta );
		}

		// Use get item to prime caches
		$this->update_item_cache( $item_id );

		// Transition item data
		$this->transition_item( $save, $item );

		// Return result
		return $result;
	}

	/**
	 * Delete an item from the database
	 *
	 * @since 3.0
	 *
	 * @param int $item_id
	 * @return boolean
	 */
	public function delete_item( $item_id = 0 ) {

		// Bail if no item ID
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) ) {
			return false;
		}

		// Get vars
		$primary = $this->get_primary_column_name();

		// Get item (before it's deleted)
		$item = $this->get_item_raw( $primary, $item_id );

		// Bail if item does not exist to delete
		if ( empty( $item ) ) {
			return false;
		}

		// Attempt to reduce this item
		$item = $this->reduce_item( 'delete', $item );

		// Bail if item was reduced to nothing
		if ( empty( $item ) ) {
			return false;
		}

		// Try to delete
		$table   = $this->get_table_name();
		$where   = array( $primary => $item_id );
		$result  = $this->get_db()->delete( $table, $where );

		// Bail on failure
		if ( $this->failed( $result ) ) {
			return false;
		}

		// Clean caches on successful delete
		$this->delete_all_item_meta( $item_id );
		$this->clean_item_cache( $item );

		// Return result
		return $result;
	}

	/**
	 * Filter an item before it is inserted of updated in the database.
	 *
	 * This method is public to allow subclasses to perform JIT manipulation
	 * of the parameters passed into it.
	 *
	 * @since 3.0
	 *
	 * @param array $item
	 * @return array
	 */
	public function filter_item( $item = array() ) {
		return (array) apply_filters_ref_array( $this->apply_prefix( "filter_{$this->item_name}_item" ), array( $item, &$this ) );
	}

	/**
	 * Shape an item from the database into the type of object it always wanted
	 * to be when it grew up (EDD_Customer, EDD_Discount, EDD_Payment, etc...)
	 *
	 * @since 3.0
	 *
	 * @param mixed ID of item, or row from database
	 * @return mixed False on error, Object of single-object class type on success
	 */
	private function shape_item( $item = 0 ) {

		// Get the item from an ID
		if ( is_numeric( $item ) ) {
			$item = $this->get_item( $item );
		}

		// Return the item if it's already shaped
		if ( $item instanceof $this->item_shape ) {
			return $item;
		}

		// Shape the item as needed
		$item = ! empty( $this->item_shape )
			? new $this->item_shape( $item )
			: (object) $item;

		// Return the item object
		return $item;
	}

	/**
	 * Validate an item before it is updated in or added to the database.
	 *
	 * @since 3.0
	 *
	 * @param array $item
	 * @return mixed False on error, Array of validated values on success
	 */
	private function validate_item( $item = array() ) {

		// Bail if item is empty
		if ( empty( $item ) ) {
			return $item;
		}

		// Loop through item attributes
		foreach ( $item as $key => $value ) {

			// Always strip slashes from all values
			$value    = stripslashes( $value );

			// Get callback for column
			$callback = $this->get_column_field( array( 'name' => $key ), 'validate' );

			// Attempt to validate
			if ( ! empty( $callback ) && is_callable( $callback ) ) {
				$validated = call_user_func( $callback, $value );

				// Bail if error
				if ( is_wp_error( $validated ) ) {
					return false;
				}

				// Update the value
				$item[ $key ] = $validated;

			// Include basic stripslashes() call
			} else {
				$item[ $key ] = $value;
			}
		}

		// Return the validated item
		return $this->filter_item( $item );
	}

	/**
	 * Reduce an item down to the keys and values the current user has the
	 * appropriate capabilities to select|insert|update|delete.
	 *
	 * Note that internally, this method works with both arrays and objects of
	 * any type, and also resets the key values. It looks weird, but is
	 * currently by design to protect the integrity of the return value.
	 *
	 * @since 3.0
	 *
	 * @param string $method select|insert|update|delete
	 * @param mixed  $item   Object|Array of keys/values to reduce
	 *
	 * @return mixed Object|Array without keys the current user does not have caps for
	 */
	private function reduce_item( $method = 'update', $item = array() ) {

		// Bail if item is empty
		if ( empty( $item ) ) {
			return $item;
		}

		// Loop through item attributes
		foreach ( $item as $key => $value ) {

			// Get callback for column
			$caps = $this->get_column_field( array( 'name' => $key ), 'caps' );

			// Unset if not explicitly allowed
			if ( empty( $caps[ $method ] ) || ! current_user_can( $caps[ $method ] ) ) {
				if ( is_array( $item ) ) {
					unset( $item[ $key ] );
				} elseif ( is_object( $item ) ) {
					$item->{$key} = null;
				}

			// Set if explicitly allowed
			} elseif ( is_array( $item ) ) {
				$item[ $key ] = $value;
			} elseif ( is_object( $item ) ) {
				$item->{$key} = $value;
			}
		}

		// Return the reduced item
		return $item;
	}

	/**
	 * Return an item comprised of all default values
	 *
	 * This is used by `add_item()` to populate known default values, to ensure
	 * new item data is always what we expect it to be.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	private function default_item() {

		// Default return value
		$retval   = array();

		// Get column names and defaults
		$names    = $this->get_columns( array(), 'and', 'name'    );
		$defaults = $this->get_columns( array(), 'and', 'default' );

		// Put together an item using default values
		foreach ( $names as $key => $name ) {
			$retval[ $name ] = $defaults[ $key ];
		}

		// Return
		return $retval;
	}

	/**
	 * Transition an item when adding or updating.
	 *
	 * This method takes the data being saved, looks for any columns that are
	 * known to transition between values, and fires actions on them.
	 *
	 * @since 3.0
	 *
	 * @param array $item
	 * @return array
	 */
	private function transition_item( $new_data = array(), $old_data = array() ) {

		// Look for transition columns
		$columns = $this->get_columns( array( 'transition' => true ), 'and', 'name' );

		// Bail if no columns to transition
		if ( empty( $columns ) ) {
			return;
		}

		// Get the item ID
		$item_id = $this->shape_item_id( $old_data );

		// Bail if item ID cannot be retrieved
		if ( empty( $item_id ) ) {
			return;
		}

		// If no old value(s), it's new
		if ( ! is_array( $old_data ) ) {
			$old_data = $new_data;

			// Set all old values to "new"
			foreach ( $old_data as $key => $value ) {
				$value            = 'new';
				$old_data[ $key ] = $value;
			}
		}

		// Compare
		$keys = array_flip( $columns );
		$new  = array_intersect_key( $new_data, $keys );
		$old  = array_intersect_key( $old_data, $keys );

		// Get the difference
		$diff = array_diff( $new, $old );

		// Bail if nothing is changing
		if ( empty( $diff ) ) {
			return;
		}

		// Do the actions
		foreach ( $diff as $key => $value ) {
			$old_value  = $old_data[ $key ];
			$new_value  = $new_data[ $key ];
			$key_action = $this->apply_prefix( "transition_{$this->item_name}_{$key}" );

			/**
			 * Fires after an object value has transitioned.
			 *
			 * @since 3.0
			 *
			 * @param mixed $old_value The value being transitioned FROM.
			 * @param mixed $new_value The value being transitioned TO.
			 * @param int   $item_Id   The ID of the item that is transitioning.
			 */
			do_action( $key_action, $old_value, $new_value, $item_id );
		}
	}

	/**
	 * Check if the query failed
	 *
	 * @since 3.0
	 *
	 * @param mixed $result
	 * @return boolean
	 */
	private function failed( $result = false ) {

		// Bail if no row exists
		if ( empty( $result ) ) {
			$retval = true;

		// Bail if an error occurred
		} elseif ( is_wp_error( $result ) ) {
			$this->last_error = $result;
			$retval           = true;

		// No errors
		} else {
			$retval = false;
		}

		// Return the result
		return $retval;
	}

	/** Meta ******************************************************************/

	/**
	 * Add meta data to an item
	 *
	 * @since 3.0
	 *
	 * @param int    $item_id
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param string $unique
	 * @return mixed
	 */
	protected function add_item_meta( $item_id = 0, $meta_key = '', $meta_value = '', $unique = false ) {

		// Bail if no meta was returned
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) || empty( $meta_key ) ) {
			return false;
		}

		// Get meta table name
		$table = $this->apply_prefix( $this->item_name );

		// Bail if no meta table exists
		if ( empty( $table ) ) {
			return false;
		}

		// Return results of get meta data
		return add_metadata( $table, $item_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Get meta data for an item
	 *
	 * @since 3.0
	 *
	 * @param int     $item_id
	 * @param string  $meta_key
	 * @param boolean $single
	 * @return mixed
	 */
	protected function get_item_meta( $item_id = 0, $meta_key = '', $single = false ) {

		// Bail if no meta was returned
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) || empty( $meta_key ) ) {
			return false;
		}

		// Get meta table name
		$table = $this->apply_prefix( $this->item_name );

		// Bail if no meta table exists
		if ( empty( $table ) ) {
			return false;
		}

		// Return results of get meta data
		return get_metadata( $table, $item_id, $meta_key, $single );
	}

	/**
	 * Update meta data for an item
	 *
	 * @since 3.0
	 *
	 * @param int    $item_id
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param string $prev_value
	 * @return mixed
	 */
	protected function update_item_meta( $item_id = 0, $meta_key = '', $meta_value = '', $prev_value = '' ) {

		// Bail if no meta was returned
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) || empty( $meta_key ) ) {
			return false;
		}

		// Get meta table name
		$table = $this->apply_prefix( $this->item_name );

		// Bail if no meta table exists
		if ( empty( $table ) ) {
			return false;
		}

		// Return results of get meta data
		return update_metadata( $table, $item_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete meta data for an item
	 *
	 * @since 3.0
	 *
	 * @param int    $item_id
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param string $delete_all
	 * @return mixed
	 */
	protected function delete_item_meta( $item_id = 0, $meta_key = '', $meta_value = '', $delete_all = false ) {

		// Bail if no meta was returned
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) || empty( $meta_key ) ) {
			return false;
		}

		// Get meta table name
		$table = $this->apply_prefix( $this->item_name );

		// Bail if no meta table exists
		if ( empty( $table ) ) {
			return false;
		}

		// Return results of get meta data
		return delete_metadata( $table, $item_id, $meta_key, $meta_value, $delete_all );
	}

	/**
	 * Get registered meta data keys
	 *
	 * This is a copy of get_registered_meta_keys() for WordPress versions lower
	 * than 4.6.0.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	private function get_registered_meta_keys() {
		global $wp_meta_keys;

		// Get the object type
		$object_type = $this->apply_prefix( $this->item_name );

		// Bail if not set or not an array
		if ( ! is_array( $wp_meta_keys ) || ! isset( $wp_meta_keys[ $object_type ] ) ) {
			return array();
		}

		// Return the keys
		return $wp_meta_keys[ $object_type ];
	}

	/**
	 * Maybe update meta values on item update/save
	 *
	 * @since 3.0
	 *
	 * @param array $meta
	 */
	private function save_extra_item_meta( $item_id = 0, $meta = array() ) {

		// Bail if there is no bulk meta to save
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) || empty( $meta ) ) {
			return;
		}

		// Get meta table name
		$table = $this->get_meta_table_name();

		// Bail if no meta table exists
		if ( empty( $table ) ) {
			return;
		}

		// Only save registered keys
		$keys = $this->get_registered_meta_keys();
		$meta = array_intersect_key( $meta, $keys );

		// Save or delete meta data
		foreach ( $meta as $key => $value ) {
			! empty( $value )
				? $this->update_item_meta( $item_id, $key, $value )
				: $this->delete_item_meta( $item_id, $key );
		}
	}

	/**
	 * Delete all meta data for an item
	 *
	 * @since 3.0
	 *
	 * @param int $item_id
	 */
	private function delete_all_item_meta( $item_id = 0 ) {

		// Bail if no meta was returned
		$item_id = $this->shape_item_id( $item_id );
		if ( empty( $item_id ) ) {
			return;
		}

		// Get meta table name
		$table = $this->get_meta_table_name();

		// Bail if no meta table exists
		if ( empty( $table ) ) {
			return;
		}

		// Guess the item ID column for the meta table
		$primary_id     = $this->get_primary_column_name();
		$item_id_column = $this->apply_prefix( "{$this->item_name}_{$primary_id}" );

		// Get meta IDs
		$sql      = "SELECT meta_id FROM {$table} WHERE {$item_id_column} = %d";
		$prepared = $this->get_db()->prepare( $sql, $item_id );
		$meta_ids = $this->get_db()->get_col( $prepared );

		// Delete all meta data for this item ID
		foreach ( $meta_ids as $mid ) {
			delete_metadata_by_mid( $table, $mid );
		}
	}

	/**
	 * Return meta table
	 *
	 * @since 3.0
	 *
	 * @return mixed Table name if exists, False if not
	 */
	private function get_meta_table_name() {

		// Maybe apply table prefix
		$table = $this->apply_prefix( $this->item_name );

		// Return table if exists, or false if not
		return _get_meta_table( $table );
	}

	/** Cache *****************************************************************/

	/**
	 * Get cache key from query_vars and query_var_defaults.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	private function get_cache_key( $group = '' ) {

		// Slice query vars
		$slice = wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) );

		// Unset `fields` so it does not effect the cache key
		unset( $slice['fields'] );

		// Setup key & last_changed
		$key          = md5( serialize( $slice ) );
		$last_changed = $this->get_last_changed_cache( $group );

		// Concatenate and return cache key
		return "get_{$this->item_name_plural}:{$key}:{$last_changed}";
	}

	/**
	 * Get the cache group, or fallback to the primary one.
	 *
	 * @since 3.0
	 *
	 * @param string $group
	 * @return string
	 */
	private function get_cache_group( $group = '' ) {

		// Get the primary column
		$primary = $this->get_primary_column_name();

		// Default return value
		$retval  = $this->cache_group;

		// Only allow non-primary groups
		if ( ! empty( $group ) && ( $group !== $primary ) ) {
			$retval = $group;
		}

		// Return the group
		return $retval;
	}

	/**
	 * Get array of which database columns have uniquely cached groups
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	private function get_cache_groups() {

		// Return value
		$cache_groups = array();

		// Get cache groups
		$groups = $this->get_columns( array( 'cache_key' => true ), 'and', 'name' );

		// Get the primary column
		$primary = $this->get_primary_column_name();

		// Setup return values
		foreach ( $groups as $name ) {
			if ( $primary !== $name ) {
				$cache_groups[ $name ] = "{$this->cache_group}-by-{$name}";
			} else {
				$cache_groups[ $name ] = $this->cache_group;
			}
		}

		// Return cache groups array
		return $cache_groups;
	}

	/**
	 * Maybe prime item & item-meta caches by querying 1 time for all un-cached
	 * items.
	 *
	 * Accepts a single ID, or an array of IDs.
	 *
	 * The reason this accepts only IDs is because it gets called immediately
	 * after an item is inserted in the database, but before items have been
	 * "shaped" into proper objects, so object properties may not be set yet.
	 *
	 * @since 3.0
	 *
	 * @param array   $item_ids
	 * @param boolean $force
	 *
	 * @return boolean False if empty
	 */
	private function prime_item_caches( $item_ids = array(), $force = false ) {

		// Bail if no items to cache
		if ( empty( $item_ids ) ) {
			return false;
		}

		// Accepts single values, so cast to array
		$item_ids = (array) $item_ids;

		// Update item caches
		if ( ! empty( $force ) || ! empty( $this->query_vars['update_item_cache'] ) ) {

			// Look for non-cached IDs
			$ids = $this->get_non_cached_ids( $item_ids, $this->cache_group );

			// Bail if IDs are cached
			if ( empty( $ids ) ) {
				return false;
			}

			// Query
			$table   = $this->get_table_name();
			$primary = $this->get_primary_column_name();
			$query   = "SELECT * FROM {$table} WHERE {$primary} IN (%s)";
			$ids     = join( ',', array_map( 'absint', $ids ) );
			$prepare = sprintf( $query, $ids );
			$results = $this->get_db()->get_results( $prepare );

			// Update item caches
			$this->update_item_cache( $results );
		}

		// Update meta data caches
		if ( ! empty( $this->query_vars['update_meta_cache'] ) ) {
			$singular = rtrim( $this->table_name, 's' ); // sic
			update_meta_cache( $singular, $item_ids );
		}
	}

	/**
	 * Update the cache for an item. Does not update item-meta cache.
	 *
	 * Accepts a single object, or an array of objects.
	 *
	 * The reason this does not accept ID's is because this gets called
	 * after an item is already updated in the database, so we want to avoid
	 * querying for it again. It's just safer this way.
	 *
	 * @since 3.0
	 *
	 * @param array $items
	 */
	private function update_item_cache( $items = array() ) {

		// Maybe query for single item
		if ( is_numeric( $items ) ) {
			$primary = $this->get_primary_column_name();
			$items   = $this->get_item_raw( $primary, $items );
		}

		// Bail if no items to cache
		if ( empty( $items ) ) {
			return false;
		}

		// Make sure items are an array (without casting objects to arrays)
		if ( ! is_array( $items ) ) {
			$items = array( $items );
		}

		// Get cache groups
		$groups = $this->get_cache_groups();

		// Loop through all items and cache them
		foreach ( $items as $item ) {
			if ( is_object( $item ) ) {
				foreach ( $groups as $key => $group ) {
					$this->cache_set( $item->{$key}, $item, $group );
				}
			}
		}

		// Update last changed
		$this->update_last_changed_cache();
	}

	/**
	 * Clean the cache for an item. Does not clean item-meta.
	 *
	 * Accepts a single object, or an array of objects.
	 *
	 * The reason this does not accept ID's is because this gets called
	 * after an item is already deleted from the database, so it cannot be
	 * queried and may not exist in the cache. It's just safer this way.
	 *
	 * @since 3.0
	 *
	 * @param array $items
	 *
	 * @return boolean
	 */
	private function clean_item_cache( $items = array() ) {

		// Bail if no items to cache
		if ( empty( $items ) ) {
			return false;
		}

		// Make sure items is an array
		$items  = (array) $items;
		$groups = $this->get_cache_groups();

		// Loop through all items and cache them
		foreach ( $items as $item ) {
			if ( is_object( $item ) ) {
				foreach ( $groups as $key => $group ) {
					$this->cache_delete( $item->{$key}, $group );
				}
			}
		}

		// Update last changed
		$this->update_last_changed_cache();
	}

	/**
	 * Update the last_changed key for the cache group
	 *
	 * @since 3.0
	 */
	private function update_last_changed_cache( $group = '' ) {

		// Fallback to microtime
		if ( empty( $this->last_changed ) ) {
			$this->set_last_changed();
		}

		// Set the last changed time for this cache group
		$this->cache_set( 'last_changed', $this->last_changed, $group );

		// Return the last changed time
		return $this->last_changed;
	}

	/**
	 * Get the last_changed key for a cache group
	 *
	 * @since 3.0
	 *
	 * @param string $group Cache group. Defaults to $this->cache_group
	 *
	 * @return int The last time a cache group was changed
	 */
	private function get_last_changed_cache( $group = '' ) {

		// Get the last changed cache value
		$last_changed = $this->cache_get( 'last_changed', $group );

		// Maybe update the last changed value
		if ( false === $last_changed ) {
			$last_changed = $this->update_last_changed_cache( $group );
		}

		// Return the last changed value for the cache group
		return $last_changed;
	}

	/**
	 * Get array of non-cached item IDs.
	 *
	 * @since 3.0
	 *
	 * @param array  $item_ids Array of item IDs
	 * @param string $group    Cache group. Defaults to $this->cache_group
	 *
	 * @return array
	 */
	private function get_non_cached_ids( $item_ids = array(), $group = '' ) {
		$retval = array();

		// Loop through item IDs
		foreach ( $item_ids as $id ) {
			$id = $this->shape_item_id( $id );

			if ( false === $this->cache_get( $id, $group ) ) {
				$retval[] = $id;
			}
		}

		// Return array of IDs
		return $retval;
	}

	/**
	 * Add a cache value for a key and group.
	 *
	 * @since 3.0
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $value  Cache value.
	 * @param string $group  Cache group. Defaults to $this->cache_group
	 * @param int    $expire Expiration.
	 */
	private function cache_add( $key = '', $value = '', $group = '', $expire = 0 ) {

		// Bail if cache invalidation is suspended
		if ( wp_suspend_cache_addition() ) {
			return;
		}

		// Bail if no cache key
		if ( empty( $key ) ) {
			return;
		}

		// Get the cache group
		$group = $this->get_cache_group( $group );

		// Delete the cache
		wp_cache_add( $key, $value, $group, $expire );
	}

	/**
	 * Get a cache value for a key and group.
	 *
	 * @since 3.0
	 *
	 * @param string  $key   Cache key.
	 * @param string  $group Cache group. Defaults to $this->cache_group
	 * @param boolean $force
	 */
	private function cache_get( $key = '', $group = '', $force = false ) {

		// Bail if no cache key
		if ( empty( $key ) ) {
			return;
		}

		// Get the cache group
		$group = $this->get_cache_group( $group );

		// Delete the cache
		return wp_cache_get( $key, $group, $force );
	}

	/**
	 * Set a cache value for a key and group.
	 *
	 * @since 3.0
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $value  Cache value.
	 * @param string $group  Cache group. Defaults to $this->cache_group
	 * @param int    $expire Expiration.
	 */
	private function cache_set( $key = '', $value = '', $group = '', $expire = 0 ) {

		// Bail if cache invalidation is suspended
		if ( wp_suspend_cache_addition() ) {
			return;
		}

		// Bail if no cache key
		if ( empty( $key ) ) {
			return;
		}

		// Get the cache group
		$group = $this->get_cache_group( $group );

		// Delete the cache
		wp_cache_set( $key, $value, $group, $expire );
	}

	/**
	 * Delete a cache key for a group.
	 *
	 * @since 3.0
	 *
	 * @global boolean $_wp_suspend_cache_invalidation
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group. Defaults to $this->cache_group
	 */
	private function cache_delete( $key = '', $group = '' ) {
		global $_wp_suspend_cache_invalidation;

		// Bail if cache invalidation is suspended
		if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
			return;
		}

		// Bail if no cache key
		if ( empty( $key ) ) {
			return;
		}

		// Get the cache group
		$group = $this->get_cache_group( $group );

		// Delete the cache
		wp_cache_delete( $key, $group );
	}

	/**
	 * Fetch raw results directly from the database.
	 *
	 * @since 3.0
	 *
	 * @param array  $cols       Columns for `SELECT`.
	 * @param array  $where_cols Where clauses. Each key-value pair in the array
	 *                           represents a column and a comparison.
	 * @param int    $limit      Optional. LIMIT value. Default 25.
	 * @param null   $offset     Optional. OFFSET value. Default null.
	 * @param string $output     Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 *                           Default OBJECT.
	 *                           With one of the first three, return an array of
	 *                           rows indexed from 0 by SQL result row number.
	 *                           Each row is an associative array (column => value, ...),
	 *                           a numerically indexed array (0 => value, ...),
	 *                           or an object. ( ->column = value ), respectively.
	 *                           With OBJECT_K, return an associative array of
	 *                           row objects keyed by the value of each row's
	 *                           first column's value.
	 *
	 * @return array|object|null Database query results.
	 */
	public function get_results( $cols = array(), $where_cols = array(), $limit = 25, $offset = null, $output = OBJECT ) {

		// Bail if no columns have been passed.
		if ( empty( $cols ) ) {
			return null;
		}

		// Fetch all the columns for the table being queried.
		$column_names = $this->get_column_names();

		// Ensure valid column names have been passed for the `SELECT` clause.
		foreach ( $cols as $index => $column ) {
			if ( ! in_array( $column, $column_names, true ) ) {
				unset( $cols[ $index ] );
			}
		}

		// Ensure valid columns have been passed for the `WHERE` clause.
		if ( ! empty( $where_cols ) ) {
			foreach ( $where_cols as $column => $values ) {
				if ( ! array_key_exists( $column, $column_names ) ) {
					unset( $where_cols[ $column ] );
				}
			}
		}

		// Setup base SQL query.
		$query  = 'SELECT ';
		$query .= implode( ',', $cols );
		$query .= " FROM {$this->get_table_name()} {$this->table_alias} ";
		$query .= ' WHERE 1=1 ';

		// Parse WHERE clauses.
		foreach ( $where_cols as $column => $compare ) {

			// Basic WHERE clause.
			if ( ! is_array( $compare ) ) {
				$query .= " AND {$this->table_alias}.{$column} = {$compare} ";

			// More complex WHERE clause.
			} else {
				$value = isset( $compare['value'] )
					? $compare['value']
					: false;

				// Skip if a value was not provided.
				if ( false === $value ) {
					continue;
				}

				$compare_clause = isset( $compare['compare'] )
					? strtoupper( $compare['compare'] )
					: '=';

				// Array (unprepared)
				if ( is_array( $compare['value'] ) ) {

					// Default to IN if clause not specified.
					if ( 'IN' !== $compare_clause && 'NOT IN' !== $compare_clause && 'BETWEEN' !== $compare_clause ) {
						$compare_clause = 'IN';
					}

					// Parse & escape for IN and NOT IN.
					if ( 'IN' === $compare_clause || 'NOT IN' === $compare_clause ) {
						$value = "('" . implode( "','", $this->get_db()->_escape( $compare['value'] ) ) . "')";

					// Parse & escape for BETWEEN.
					} elseif ( is_array( $value ) && 2 === count( $value ) && 'BETWEEN' === $compare_clause ) {
						$value = " {$this->get_db()->_escape( $value[0] )} AND {$this->get_db()->_escape( $value[1] )} ";
					}
				}

				// Add WHERE clause.
				$query .= " AND {$this->table_alias}.{$column} {$compare_clause} {$value} ";
			}
		}

		// Maybe set an offset.
		if ( ! empty( $offset ) ) {
			$offset_values = explode( ',', $offset );
			$offset_values = array_filter( $offset_values, 'intval' );
			$offset        = implode( ',', $offset_values );

			$query .= " OFFSET {$offset} ";
		}

		// Maybe set a limit.
		if ( ! empty( $limit ) && $limit > 0 ) {
			$limit  = intval( $limit );
			$query .= " LIMIT {$limit} ";
		}

		// Execute query.
		$results = $this->get_db()->get_results( $query, $output );

		// Return results.
		return $results;
	}
}