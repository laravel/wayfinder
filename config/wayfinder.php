<?php

return [

    /*
     * Rename generated javascript methods to prevent using reserved words.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Lexical_grammar#reserved_words
     */
    'rename_methods' => [
        'delete' => 'deleteMethod',
    ],

];
