<?php

namespace EmailWP\Common\Http;

class Http
{

    public function end_rest_success($data)
    {
        return [
            'status' => 'S',
            'data' => $data
        ];
    }

    public function end_rest_error($data)
    {
        return [
            'status' => 'E',
            'data' => $data
        ];
    }
}
