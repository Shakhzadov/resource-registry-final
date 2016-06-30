<?php
return [
    'community/show' => [
        'type' => 2,
    ],
    'user/changeemail' => [
        'type' => 2,
    ],
    'community/addcomm' => [
        'type' => 2,
    ],
    'community/view' => [
        'type' => 2,
    ],
    'community/index' => [
        'type' => 2,
    ],
    'community/update' => [
        'type' => 2,
    ],
    'resource/view' => [
        'type' => 2,
    ],
    'resource/index' => [
        'type' => 2,
    ],
    'resource/getregisterkey' => [
        'type' => 2,
    ],
    'resource/search' => [
        'type' => 2,
    ],
    'resource/create' => [
        'type' => 2,
    ],
    'resource/registrationnumber' => [
        'type' => 2,
    ],
    'resource/gettingdata' => [
        'type' => 2,
    ],
    'resource/creatingrequest' => [
        'type' => 2,
    ],
    'resource/additiondata' => [
        'type' => 2,
    ],
    'resource_class/index' => [
        'type' => 2,
    ],
    'resource_class/search' => [
        'type' => 2,
    ],
    'resource_class/addresourceclass' => [
        'type' => 2,
    ],
    'resource_class/changeactivationstatus' => [
        'type' => 2,
    ],
    'user/userdata' => [
        'type' => 2,
    ],
    'user/edituserdata' => [
        'type' => 2,
    ],
    'user/getrole' => [
        'type' => 2,
    ],
    'user/adduser' => [
        'type' => 2,
    ],
    'user/changeactivationstatus' => [
        'type' => 2,
    ],
    'user/changerole' => [
        'type' => 2,
    ],
    'user/changecommunity' => [
        'type' => 2,
    ],
    'request/showrequest' => [
        'type' => 2,
    ],
    'request/addreq' => [
        'type' => 2,
    ],
    'search/search' => [
        'type' => 2,
    ],
    'attribute_class_view/index' => [
        'type' => 2,
    ],
    'resource_class/attribute' => [
        'type' => 2,
    ],
    'attribute_class_view/attribute' => [
        'type' => 2,
    ],
    'attribute_class_view/addattribute' => [
        'type' => 2,
    ],
    'attribute_class_view/findlastattributeid' => [
        'type' => 2,
    ],
    'attribute_class_view/deleteattribute' => [
        'type' => 2,
    ],
    'attribute_class_view/findfilteredattributes' => [
        'type' => 2,
    ],
    'attribute_class_view/findfilteredattributesforeachresourceclass' => [
        'type' => 2,
    ],
    'resource_attribute/findglobalattributes' => [
        'type' => 2,
    ],
    'attribute_class_view/findallattributes' => [
        'type' => 2,
    ],
    'resource/export' => [
        'type' => 2,
    ],
    'resource/extract' => [
        'type' => 2,
    ],
    'user/changepassloged' => [
        'type' => 2,
    ],
    'site/users' => [
        'type' => 2,
    ],
    'user' => [
        'type' => 1,
        'ruleName' => 'userGroup',
        'children' => [
            'resource/view',
            'resource/index',
            'resource/search',
            'resource_class/index',
            'resource/getregisterkey',
            'resource/create',
            'resource/gettingdata',
            'resource/additiondata',
            'request/showrequest',
            'request/addreq',
            'resource/creatingrequest',
            'resource/registrationnumber',
            'attribute_class_view/findallattributes',
            'user/changeemail',
            'user/changepassloged',
        ],
    ],
    'registrar' => [
        'type' => 1,
        'ruleName' => 'userGroup',
        'children' => [
            'user',
            'search/search',
            'resource/index',
            'resource_attribute/findglobalattributes',
            'attribute_class_view/findfilteredattributes',
            'attribute_class_view/findfilteredattributesforeachresourceclass',
            'resource_class/search',
            'attribute_class_view/findallattributes',
            'resource/registrationnumber',
            'resource/export',
            'user/changeemail',
            'resource/extract',
            'user/changepassloged',
        ],
    ],
    'commissioner' => [
        'type' => 1,
        'ruleName' => 'userGroup',
        'children' => [
            'user',
            'resource/index',
            'user/userdata',
            'user/edituserdata',
            'user/getrole',
            'user/changeactivationstatus',
            'user/changerole',
            'user/adduser',
            'community/index',
            'community/show',
            'user/changeemail',
            'user/changepassloged',
        ],
    ],
    'admin' => [
        'type' => 1,
        'ruleName' => 'userGroup',
        'children' => [
            'user/userdata',
            'resource/index',
            'user/edituserdata',
            'user/getrole',
            'user/changeactivationstatus',
            'user/changerole',
            'user/changecommunity',
            'user/adduser',
            'community/addcomm',
            'community/show',
            'community/view',
            'community/update',
            'community/index',
            'resource_class/addresourceclass',
            'resource_class/search',
            'resource_class/changeactivationstatus',
            'resource_class/index',
            'attribute_class_view/index',
            'resource_class/attribute',
            'attribute_class_view/attribute',
            'attribute_class_view/addattribute',
            'attribute_class_view/findlastattributeid',
            'attribute_class_view/deleteattribute',
            'resource_attribute/findglobalattributes',
            'attribute_class_view/findfilteredattributes',
            'attribute_class_view/findfilteredattributesforeachresourceclass',
            'user/changeemail',
            'user/changepassloged',
            'site/users',
        ],
    ],
];
