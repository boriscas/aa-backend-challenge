<?php

namespace App\Model\Dto;

class ParserParameter
{
    public const INTERNAL_LINK_REGEX = '^/.*$';
    public const EXTERNAL_LINK_REGEX = '^(https|http)://.*$';
    public const QUERY_ALL_TEXT_NODES_XPATH = '//text()[not(parent::style)][not(parent::script)]';
    public const URL_DOMAIN_REGEX = '^(?:https?:\/\/)?(?:[^@\/\n]+@)?(?:www\.)?([^:\/?\n]+)';
}