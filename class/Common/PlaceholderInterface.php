<?php

namespace EmailWP\Common;

interface PlaceholderInterface
{
    public function get_id();
    public function get_variables();
    public function save_data($data);
    public function load_data($data);
    public function replace($key, $data);
}
