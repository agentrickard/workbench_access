<?php

class workbench_access_handler_field_section extends views_handler_field {
  // No query for this field.
  function query() {}
  
  function render($values) {
    return '';
  }
}
