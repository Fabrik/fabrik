module.exports = {
    'pluginFolders'   : [
        'element',
        'cron',
        'form',
        'list',
        'validationrule',
        'visualization'
    ],
    'corePackageFiles': [
        'mod_fabrik_form_{version}.zip',
        'mod_fabrik_list_{version}.zip',
        'plg_fabrik_system_{version}.zip',
        'plg_fabrik_schedule_{version}.zip',
        'plg_fabrik_content_{version}.zip',
        'plg_fabrik_cron_email_{version}.zip',
        'plg_fabrik_cron_php_{version}.zip',
        'plg_fabrik_element_button_{version}.zip',
        'plg_fabrik_element_checkbox_{version}.zip',
        'plg_fabrik_element_databasejoin_{version}.zip',
        'plg_fabrik_element_date_{version}.zip',
        'plg_fabrik_element_jdate_{version}.zip',
        'plg_fabrik_element_display_{version}.zip',
        'plg_fabrik_element_dropdown_{version}.zip',
        'plg_fabrik_element_field_{version}.zip',
        'plg_fabrik_element_fileupload_{version}.zip',
        'plg_fabrik_element_googlemap_{version}.zip',
        'plg_fabrik_element_image_{version}.zip',
        'plg_fabrik_element_internalid_{version}.zip',
        'plg_fabrik_element_link_{version}.zip',
        'plg_fabrik_element_radiobutton_{version}.zip',
        'plg_fabrik_element_textarea_{version}.zip',
        'plg_fabrik_element_user_{version}.zip',
        'plg_fabrik_form_email_{version}.zip',
        'plg_fabrik_form_php_{version}.zip',
        'plg_fabrik_form_receipt_{version}.zip',
        'plg_fabrik_form_redirect_{version}.zip',
        'plg_fabrik_list_copy_{version}.zip',
        'plg_fabrik_list_php_{version}.zip',
        'plg_fabrik_validationrule_isgreaterorlessthan_{version}.zip',
        'plg_fabrik_validationrule_notempty_{version}.zip',
        'plg_fabrik_validationrule_php_{version}.zip',
        'plg_fabrik_validationrule_regex_{version}.zip',
        'plg_fabrik_validationrule_isemail_{version}.zip',
        'plg_fabrik_visualization_chart_{version}.zip',
        'plg_fabrik_visualization_fullcalendar_{version}.zip',
	    'plg_fabrik_visualization_calendar_{version}.zip',
        'plg_fabrik_visualization_googlemap_{version}.zip',
        'plg_fabrik_visualization_media_{version}.zip',
        'plg_fabrik_visualization_slideshow_{version}.zip',
        'com_fabrik_{version}.zip'
    ],
    'modules'         : [
        {
            'name'    : 'Fabrik List Module',
            'path'    : 'modules/mod_fabrik_list',
            'fileName': 'mod_fabrik_list_{version}.zip',
            'element' : 'mod_fabrik_list',
            'xmlFile' : 'mod_fabrik_list.xml'
        },
        {
            'name'    : 'Fabrik Form Module',
            'path'    : 'modules/mod_fabrik_form',
            'fileName': 'mod_fabrik_form_{version}.zip',
            'element' : 'mod_fabrik_form',
            'xmlFile' : 'mod_fabrik_form.xml'
        },
        {
            'name'    : 'Fabrik Admin Form Module',
            'path'    : 'administrator/modules/mod_fabrik_form',
            'fileName': 'mod_fabrik_admin_form_{version}.zip',
            'element' : 'mod_fabrik_form',
            'xmlFile' : 'mod_fabrik_admin_form.xml',
            'client'  : 'administrator'
        },
        {
            'name'    : 'Fabrik Admin List Module',
            'path'    : 'administrator/modules/mod_fabrik_list',
            'fileName': 'mod_fabrik_admin_list_{version}.zip',
            'element' : 'mod_fabrik_list',
            'xmlFile' : 'mod_fabrik_admin_list.xml',
            'client'  : 'administrator'
        },
        {
            'name'    : 'Fabrik Admin QuickIcon Module',
            'path'    : 'administrator/modules/mod_fabrik_quickicon',
            'fileName': 'mod_fabrik_admin_quickicon_{version}.zip',
            'element' : 'mod_fabrik_quickicon',
            'xmlFile' : 'mod_fabrik_admin_quickicon.xml',
            'client'  : 'administrator'
        },
        {
            'name'    : 'Fabrik Admin Visualization Module',
            'path'    : 'administrator/modules/mod_fabrik_visualization',
            'fileName': 'mod_fabrik_admin_visualization_{version}.zip',
            'element' : 'mod_fabrik_visualization',
            'xmlFile' : 'mod_fabrik_admin_visualization.xml',
            'client'  : 'administrator'
        }],
    'plugins'         : {
        'system'   : [
            {
                'name'    : 'Fabrik System Plugin',
                'path'    : 'plugins/system/fabrik',
                'fileName': 'plg_fabrik_system_{version}.zip',
                'element' : 'fabrik',
                'xmlFile' : 'plg_system_fabrik.xml'
            },
            {
                'name'    : 'Fabrik Cron Plugin',
                'path'    : 'plugins/system/fabrikcron',
                'fileName': 'plg_fabrik_schedule_{version}.zip',
                'element' : 'fabrikcron',
                'xmlFile' : 'plg_system_fabrikcron.xml'
            }
        ],
        'comprofiler': [
            {
                'name'    : 'Community Builder: Fabrik User Plugin',
                'path'    : 'components/com_comprofiler/plugin/user/plug_fabrik',
                'fileName': 'plg_community_builder_fabrik_user_{version}.zip',
                'element' : 'fbk.fabrik',
                'xmlFile' : 'plg_comprofiler_fabrik.xml'
            }
        ],
        'community': [
            {
                'name'    : 'JSocial: Fabrik User Plugin',
                'path'    : 'plugins/community/fabrik',
                'fileName': 'plg_jsocial_fabrik_{version}.zip',
                'element' : 'fabrik',
                'xmlFile' : 'plg_jsocial_fabrik.xml'
            }],
        'content'  : [
            {
                'name'    : 'Fabrik Content Plugin',
                'path'    : 'plugins/content/fabrik',
                'fileName': 'plg_fabrik_content_{version}.zip',
                'element' : 'fabrik',
                'xmlFile' : 'plg_fabrik_content.xml'
            }],
        'search'   : [
            {
                'name'    : 'Fabrik Search Plugin',
                'path'    : 'plugins/search/fabrik',
                'fileName': 'plg_fabrik_search_{version}.zip',
                'element' : 'fabrik',
                'xmlFile' : 'plg_fabrik_search.xml'
            }
        ]
    },
    'libraries'         : {
        'fabrik'   : [
            {
                'name'    : 'Fabrik Library',
                'path'    : 'libraries/fabrik',
                'fileName': 'lib_fabrik_{version}.zip',
                'element' : 'fabrik',
                'xmlFile' : 'fabrik.xml'
            }
        ]
    }
}
