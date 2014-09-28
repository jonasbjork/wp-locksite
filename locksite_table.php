<?php

if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class LockSite_Table extends WP_List_Table {

    private $lock_site_table_data = array();

    function get_columns() {
        $columns = array(
            'cb' => '',
            'blog_id' => 'Blog Id',
            'domain' => 'Domain',
            'path' => 'Path'
        );
        return $columns;
    }

    function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array(
            'blog_id'   => array('blog_id', false),
            'domain'    => array('domain', true),
            'path'      => array('path', true)
        );
        $this->_column_headers = array($columns, $hidden, $sortable);
        usort($this->lock_site_table_data, array(&$this, 'usort_reorder'));
        $this->items = $this->lock_site_table_data;
    }

    function set_data($data) {
        $this->lock_site_table_data = $data;
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'blog_id':
            case 'domain':
            case 'path':
                return $item[ $column_name ];
            default:
                return print_r($item, true);
        }
    }

    function column_cb($item) {
        $checked = '';
        $lsc = get_site_option('locksite_config', false);
        if ($lsc != false) { $lsc = unserialize($lsc); }
        if (in_array($item['blog_id'], $lsc)){
            $checked = ' checked="checked"';
        }
        return sprintf('<input type="checkbox" name="site[]" value="%s" %s/>', $item['blog_id'], $checked);
    }

    function usort_reorder($a, $b) {
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'blog_id';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
        $result = strcmp($a[$orderby], $b[$orderby]);
        return ($order === 'asc') ? $result : -$result;
    }


}