<?php

/*
 * This file is part of the Active Collab Logger.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$header = <<<EOF
This file is part of the Active Collab Logger.

(c) A51 doo <info@activecollab.com>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

return (new Config('psr2'))
    ->setRules(
        [
            'header_comment' => [
                'header' => $header,
                'location' => 'after_open',
            ],
            'no_whitespace_before_comma_in_array' => true,
            'whitespace_after_comma_in_array' => true,
            'no_multiline_whitespace_around_double_arrow' => true,
            'hash_to_slash_comment' => true,
            'include' => true,
            'no_alias_functions' => false,
            'trailing_comma_in_multiline_array' => true,
            'no_leading_namespace_whitespace' => true,
            'no_blank_lines_after_class_opening' => true,
            'no_blank_lines_after_phpdoc' => true,
            'phpdoc_scalar' => true,
            'phpdoc_summary' => true,
            'self_accessor' => false,
            'no_trailing_comma_in_singleline_array' => true,
            'single_blank_line_before_namespace' => true,
            'space_after_semicolon' => true,
            'no_singleline_whitespace_before_semicolons' => true,
            'cast_spaces' => true,
            'standardize_not_equals' => true,
            'ternary_operator_spaces' => true,
            'trim_array_spaces' => true,
            'no_unused_imports' => true,
            'no_whitespace_in_blank_line' => true,
            'ordered_imports' => true,
            'array_syntax' => ['syntax' => 'short'],
            'phpdoc_align' => true,
            'return_type_declaration' => true,
            'single_blank_line_at_eof' => true,
            'single_line_after_imports' => true,
            'single_quote' => true,
            'phpdoc_separation' => false,
            'phpdoc_no_package' => false,
            'no_mixed_echo_print' => false,
            'concat_space' => false,
            'simplified_null_return' => false,
            'blank_line_before_return' => true,
            'class_attributes_separation' => [
                'elements' => [],
            ],
            'linebreak_after_opening_tag' => true,
            'native_function_casing' => true,
            'no_closing_tag' => true,
            'no_empty_comment' => true,
            'no_empty_statement' => true,
            'no_leading_import_slash' => true,
            'lowercase_constants' => true,
            'lowercase_cast' => true,
            'lowercase_keywords' => true,
        ]
    )
    ->setFinder(
        (new Finder())->in(
            [
                __DIR__ . '/src',
                __DIR__ . '/test',
            ]
        )
    );
