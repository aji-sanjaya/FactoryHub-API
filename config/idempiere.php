<?php

return [

    'client_id' => (int) env('IDEMPIERE_CLIENT_ID', 1000000),
    'org_id' => (int) env('IDEMPIERE_ORG_ID', 0),
    'tenant' => [
        'name' => env('IDEMPIERE_TENANT_NAME', 'PT Adyawinsa Plastics Industry'),
    ],

    /*
    |--------------------------------------------------------------------------
    | iDempiere API
    |--------------------------------------------------------------------------
    */

    'api' => [
        'base_url' => env('IDEMPIERE_API_BASE_URL', 'https://api-erp-dev.adyawinsa.com/api/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Configuration
    |--------------------------------------------------------------------------
    */

    'create-pr' => [
        'doc_types' => [
            'purchase_requisition' => (int) env('IDEMPIERE_DOC_TYPE_PURCHASE_REQUISITION_ID', 1000018),
        ],
        'defaults' => [
            'document_status_label' => env('IDEMPIERE_PR_DEFAULT_STATUS_LABEL', 'Drafted'),
        ],
        'statuses' => [
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_PR_COMPLETED_STATUSES', 'CO,CL'))),
            'draft' => array_map('trim', explode(',', env('IDEMPIERE_PR_DRAFT_STATUSES', 'DR,IN'))),
            'in_progress' => env('IDEMPIERE_PR_IN_PROGRESS_STATUS', 'IP'),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_PR_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'printable' => array_map('trim', explode(',', env('IDEMPIERE_PR_PRINTABLE_STATUSES', 'IP,CO'))),
            'line_editable' => array_map('trim', explode(',', env('IDEMPIERE_PR_LINE_EDITABLE_STATUSES', 'DR,IN,IP'))),
            'action_blocked' => array_map('trim', explode(',', env('IDEMPIERE_PR_ACTION_BLOCKED_STATUSES', 'CL,VO,RE'))),
            'filter_options' => [
                ['value' => 'DR', 'label' => 'Drafted'],
                ['value' => 'CO', 'label' => 'Completed'],
                ['value' => 'IP', 'label' => 'In Progress'],
                ['value' => 'CL', 'label' => 'Closed'],
                ['value' => 'IN', 'label' => 'Invalid'],
            ],
            'badge_classes' => [
                'Completed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'Closed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'In Progress' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Approved' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Not Approved' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
            ],
        ],
        'workflow' => [
            'allowed_actions' => array_values(array_unique(array_merge(
                array_diff(array_map('trim', explode(',', env('IDEMPIERE_PR_ALLOWED_ACTIONS', 'CO,PR,VO,RE'))), ['CL']),
                ['RE']
            ))),
            'complete_action' => env('IDEMPIERE_PR_COMPLETE_ACTION', 'CO'),
            'prepare_action' => env('IDEMPIERE_PR_PREPARE_ACTION', 'PR'),
            'void_action' => env('IDEMPIERE_PR_VOID_ACTION', 'VO'),
            'reactivate_action' => env('IDEMPIERE_PR_REACTIVATE_ACTION', 'RE'),
            'reactivate_from' => array_map('trim', explode(',', env('IDEMPIERE_PR_REACTIVATE_FROM', 'CO,NA'))),
            'standard_from' => array_map('trim', explode(',', env('IDEMPIERE_PR_STANDARD_ACTION_FROM', 'DR,IN,IP'))),
            'action_labels' => [
                'CO' => 'Complete',
                'PR' => 'Prepare',
                'VO' => 'Void',
                'RE' => 'Re-Active',
            ],
            'confirmation_messages' => [
                'CO' => 'Are you sure you want to complete this document?',
                'PR' => 'Are you sure you want to prepare this document?',
                'VO' => 'Are you sure you want to void this document? This action cannot be undone!',
                'RE' => 'Are you sure you want to re-activate this document?',
            ],
        ],
        'limits' => [
            'list_per_page' => (int) env('IDEMPIERE_PR_LIST_PER_PAGE', 10),
            'line_default_per_page' => (int) env('IDEMPIERE_PR_LINE_DEFAULT_PER_PAGE', 10),
            'line_per_page_options' => array_map('intval', array_map('trim', explode(',', env('IDEMPIERE_PR_LINE_PER_PAGE_OPTIONS', '10,25,50,100')))),
            'products_per_page' => (int) env('IDEMPIERE_PR_PRODUCTS_PER_PAGE', 25),
            'line_increment' => (int) env('IDEMPIERE_PR_LINE_INCREMENT', 10),
        ],
    ],

    'approval-pr' => [
        'defaults' => [
            'status_filter' => env('IDEMPIERE_APPROVAL_PR_DEFAULT_STATUS', 'IP'),
            'all_filter_value' => env('IDEMPIERE_APPROVAL_PR_ALL_FILTER_VALUE', 'ALL'),
        ],
        'statuses' => [
            'pending' => env('IDEMPIERE_APPROVAL_PR_PENDING_STATUS', 'IP'),
            'approved' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PR_APPROVED_STATUSES', 'CO,CL'))),
            'rejected' => env('IDEMPIERE_APPROVAL_PR_REJECTED_STATUS', 'VO'),
            'actionable' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PR_ACTIONABLE_STATUSES', 'IP'))),
            'exclude_from_list' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PR_EXCLUDED_STATUSES', 'DR,CL'))),
            'filter_aliases' => [
                'APPROVED' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PR_FILTER_APPROVED', 'CO,CL'))),
                'REJECTED' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PR_FILTER_REJECTED', 'VO'))),
            ],
            'filter_options' => [
                ['value' => 'IP', 'label' => 'Pending'],
                ['value' => 'APPROVED', 'label' => 'Approved'],
                ['value' => 'REJECTED', 'label' => 'Rejected'],
                ['value' => 'ALL', 'label' => 'All'],
            ],
            'labels' => [
                'DR' => 'Drafted',
                'IN' => 'Drafted',
                'IP' => 'In Progress',
                'AP' => 'Approved',
                'CO' => 'Completed',
                'CL' => 'Closed',
                'VO' => 'Voided',
                'RE' => 'Reversed',
            ],
            'badge_classes' => [
                'Completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'Closed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'Approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'In Progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                'Drafted' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                'Voided' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                'Rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                'Reversed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            ],
        ],
        'workflow' => [
            'table_id' => (int) env('IDEMPIERE_APPROVAL_PR_WORKFLOW_TABLE_ID', 702),
            'open_state' => env('IDEMPIERE_APPROVAL_PR_WORKFLOW_OPEN_STATE', 'OS'),
            'allowed_actions' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PR_ALLOWED_ACTIONS', 'APPROVE,REJECT'))),
            'endpoints' => [
                'APPROVE' => env('IDEMPIERE_APPROVAL_PR_APPROVE_ENDPOINT', 'workflow/approve'),
                'REJECT' => env('IDEMPIERE_APPROVAL_PR_REJECT_ENDPOINT', 'workflow/reject'),
            ],
            'custom_column_statuses' => [
                'APPROVE' => env('IDEMPIERE_APPROVAL_PR_CUSTOM_APPROVE_STATUS', 'AP'),
                'REJECT' => env('IDEMPIERE_APPROVAL_PR_CUSTOM_REJECT_STATUS', 'RE'),
            ],
            'action_labels' => [
                'APPROVE' => 'Approve',
                'REJECT' => 'Reject',
            ],
            'confirmation_titles' => [
                'APPROVE' => 'Approve Request?',
                'REJECT' => 'Reject Request?',
            ],
            'confirm_button_text' => [
                'APPROVE' => 'Yes, Approve',
                'REJECT' => 'Yes, Reject',
            ],
            'success_messages' => [
                'APPROVE' => 'Approved successfully.',
                'REJECT' => 'Rejected successfully.',
            ],
            'no_activity_message' => env('IDEMPIERE_APPROVAL_PR_NO_ACTIVITY_MESSAGE', 'No active workflow activity found for your role on this document.'),
        ],
        'limits' => [
            'list_per_page' => (int) env('IDEMPIERE_APPROVAL_PR_LIST_PER_PAGE', 10),
            'select2_per_page' => (int) env('IDEMPIERE_APPROVAL_PR_SELECT2_PER_PAGE', 10),
        ],
    ],

    'approval-po' => [
        'defaults' => [
            'status_filter' => env('IDEMPIERE_APPROVAL_PO_DEFAULT_STATUS', 'IP'),
            'all_filter_value' => env('IDEMPIERE_APPROVAL_PO_ALL_FILTER_VALUE', 'ALL'),
            'is_so_trx' => env('IDEMPIERE_APPROVAL_PO_IS_SO_TRX', 'N'),
        ],
        'statuses' => [
            'pending' => env('IDEMPIERE_APPROVAL_PO_PENDING_STATUS', 'IP'),
            'approved' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PO_APPROVED_STATUSES', 'CO,CL'))),
            'rejected' => env('IDEMPIERE_APPROVAL_PO_REJECTED_STATUS', 'VO'),
            'actionable' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PO_ACTIONABLE_STATUSES', 'IP'))),
            'exclude_from_list' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PO_EXCLUDED_STATUSES', 'DR,CL'))),
            'filter_aliases' => [
                'APPROVED' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PO_FILTER_APPROVED', 'CO,CL'))),
                'REJECTED' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PO_FILTER_REJECTED', 'VO'))),
            ],
            'filter_options' => [
                ['value' => 'IP', 'label' => 'Pending'],
                ['value' => 'APPROVED', 'label' => 'Approved'],
                ['value' => 'REJECTED', 'label' => 'Rejected'],
                ['value' => 'ALL', 'label' => 'All'],
            ],
            'labels' => [
                'DR' => 'Drafted',
                'IN' => 'Drafted',
                'IP' => 'In Progress',
                'AP' => 'Approved',
                'CO' => 'Completed',
                'CL' => 'Closed',
                'VO' => 'Voided',
                'RE' => 'Reversed',
            ],
            'badge_classes' => [
                'Completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'Closed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'Approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'In Progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                'Drafted' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                'Voided' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                'Rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                'Reversed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            ],
        ],
        'workflow' => [
            'table_id' => (int) env('IDEMPIERE_APPROVAL_PO_WORKFLOW_TABLE_ID', 259),
            'open_state' => env('IDEMPIERE_APPROVAL_PO_WORKFLOW_OPEN_STATE', 'OS'),
            'allowed_actions' => array_map('trim', explode(',', env('IDEMPIERE_APPROVAL_PO_ALLOWED_ACTIONS', 'APPROVE,REJECT'))),
            'endpoints' => [
                'APPROVE' => env('IDEMPIERE_APPROVAL_PO_APPROVE_ENDPOINT', 'workflow/approve'),
                'REJECT' => env('IDEMPIERE_APPROVAL_PO_REJECT_ENDPOINT', 'workflow/reject'),
            ],
            'custom_column_statuses' => [
                'APPROVE' => env('IDEMPIERE_APPROVAL_PO_CUSTOM_APPROVE_STATUS', 'AP'),
                'REJECT' => env('IDEMPIERE_APPROVAL_PO_CUSTOM_REJECT_STATUS', 'RE'),
            ],
            'action_labels' => [
                'APPROVE' => 'Approve',
                'REJECT' => 'Reject',
            ],
            'confirmation_titles' => [
                'APPROVE' => 'Approve Request?',
                'REJECT' => 'Reject Request?',
            ],
            'confirm_button_text' => [
                'APPROVE' => 'Yes, Approve',
                'REJECT' => 'Yes, Reject',
            ],
            'success_messages' => [
                'APPROVE' => 'Approved successfully.',
                'REJECT' => 'Rejected successfully.',
            ],
            'no_activity_message' => env('IDEMPIERE_APPROVAL_PO_NO_ACTIVITY_MESSAGE', 'No active workflow activity found for your role on this document.'),
        ],
        'limits' => [
            'list_per_page' => (int) env('IDEMPIERE_APPROVAL_PO_LIST_PER_PAGE', 10),
            'select2_per_page' => (int) env('IDEMPIERE_APPROVAL_PO_SELECT2_PER_PAGE', 10),
        ],
    ],

    'create-po' => [
        'doc_types' => [
            'purchase_order' => (int) env('IDEMPIERE_DOC_TYPE_PURCHASE_ORDER_ID', 1000048),
            'base_type' => env('IDEMPIERE_DOC_BASE_TYPE_PURCHASE_ORDER', 'POO'),
        ],
        'defaults' => [
            'is_so_trx' => env('IDEMPIERE_PO_IS_SO_TRX', 'N'),
            'delivery_via_rule' => env('IDEMPIERE_PO_DELIVERY_VIA_RULE', 'P'),
            'priority_rule' => env('IDEMPIERE_PO_PRIORITY_RULE', '5'),
            'currency_id' => (int) env('IDEMPIERE_PO_CURRENCY_ID', 303),
            'payment_rule' => env('IDEMPIERE_PO_PAYMENT_RULE', 'P'),
            'payment_term_id' => (int) env('IDEMPIERE_PO_PAYMENT_TERM_ID', 1000007),
            'document_status_label' => env('IDEMPIERE_PO_DEFAULT_STATUS_LABEL', 'Draft'),
        ],
        'statuses' => [
            'draft' => array_map('trim', explode(',', env('IDEMPIERE_PO_DRAFT_STATUSES', 'DR'))),
            'in_progress' => array_map('trim', explode(',', env('IDEMPIERE_PO_IN_PROGRESS_STATUSES', 'IP'))),
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_PO_COMPLETED_STATUSES', 'CO,CL'))),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_PO_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'printable' => array_map('trim', explode(',', env('IDEMPIERE_PO_PRINTABLE_STATUSES', 'IP,CO,DR'))),
            'line_editable' => array_map('trim', explode(',', env('IDEMPIERE_PO_LINE_EDITABLE_STATUSES', 'DR,IN,IP'))),
            'filter_options' => [
                ['value' => 'DR', 'label' => 'Drafted'],
                ['value' => 'CO', 'label' => 'Completed'],
                ['value' => 'IP', 'label' => 'In Progress'],
                ['value' => 'CL', 'label' => 'Closed'],
                ['value' => 'IN', 'label' => 'Invalid'],
            ],
            'badge_classes' => [
                'Completed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'Closed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'In Progress' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Approved' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
            ],
            'header_badge_classes' => [
                'NA' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'VO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'RE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'CO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'CL' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'AP' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'IP' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'DR' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            ],
            'labels' => [
                'DR' => 'Draft',
                'IP' => 'In Progress',
                'CO' => 'Completed',
                'CL' => 'Closed',
                'VO' => 'Voided',
                'RE' => 'Reversed',
                'AP' => 'Approved',
                'NA' => 'Not Approved',
                'IN' => 'Invalid',
            ],
        ],
        'limits' => [
            'list_per_page' => (int) env('IDEMPIERE_PO_LIST_PER_PAGE', 10),
            'line_default_per_page' => (int) env('IDEMPIERE_PO_LINE_DEFAULT_PER_PAGE', 10),
            'line_per_page_options' => array_map('intval', array_map('trim', explode(',', env('IDEMPIERE_PO_LINE_PER_PAGE_OPTIONS', '10,25,50')))),
            'products_per_page' => (int) env('IDEMPIERE_PO_PRODUCTS_PER_PAGE', 25),
            'vendor_search' => (int) env('IDEMPIERE_PO_VENDOR_LIMIT', 100),
            'requisition_modal' => (int) env('IDEMPIERE_PO_REQUISITION_MODAL_LIMIT', 100),
            'requisition_min_search_length' => (int) env('IDEMPIERE_PO_REQUISITION_MIN_SEARCH_LENGTH', 3),
        ],
        'workflow' => [
            'table_name' => env('IDEMPIERE_PO_WORKFLOW_TABLE_NAME', 'C_Order'),
            'allowed_actions' => array_map('trim', explode(',', env('IDEMPIERE_PO_ALLOWED_ACTIONS', 'CO,PR,VO,CL,RE'))),
            'complete_action' => env('IDEMPIERE_PO_COMPLETE_ACTION', 'CO'),
            'prepare_action' => env('IDEMPIERE_PO_PREPARE_ACTION', 'PR'),
            'void_action' => env('IDEMPIERE_PO_VOID_ACTION', 'VO'),
            'close_action' => env('IDEMPIERE_PO_CLOSE_ACTION', 'CL'),
            'reactivate_action' => env('IDEMPIERE_PO_REACTIVATE_ACTION', 'RE'),
            'reactivate_from' => array_map('trim', explode(',', env('IDEMPIERE_PO_REACTIVATE_FROM', 'CO'))),
            'complete_void_from' => array_map('trim', explode(',', env('IDEMPIERE_PO_COMPLETE_VOID_FROM', 'NA,IP'))),
            'standard_blocked' => array_map('trim', explode(',', env('IDEMPIERE_PO_STANDARD_BLOCKED_STATUSES', 'CL,VO,RE,IP'))),
            'action_labels' => [
                'CO' => 'Complete',
                'PR' => 'Prepare',
                'VO' => 'Void',
                'CL' => 'Close',
                'RE' => 'Re-Activate',
            ],
            'confirmation_messages' => [
                'CO' => 'Are you sure you want to complete this document?',
                'PR' => 'Are you sure you want to prepare this document?',
                'VO' => 'Are you sure you want to void this document? This action cannot be undone!',
                'CL' => 'Are you sure you want to close this document?',
                'RE' => 'Are you sure you want to re-activate this document? This will set it back to In Progress.',
            ],
        ],
    ],

    'create-gr' => [
        'doc_types' => [
            'material_receipt' => (int) env('IDEMPIERE_DOC_TYPE_MATERIAL_RECEIPT_ID', 1000014),
            'base_type' => env('IDEMPIERE_DOC_BASE_TYPE_MATERIAL_RECEIPT', 'MMR'),
        ],
        'defaults' => [
            'movement_type' => env('IDEMPIERE_GR_MOVEMENT_TYPE', 'V+'),
            'is_so_trx' => env('IDEMPIERE_GR_IS_SO_TRX', 'N'),
            'document_status_label' => env('IDEMPIERE_GR_DEFAULT_STATUS_LABEL', 'Draft'),
        ],
        'statuses' => [
            'draft' => array_map('trim', explode(',', env('IDEMPIERE_GR_DRAFT_STATUSES', 'DR'))),
            'in_progress' => array_map('trim', explode(',', env('IDEMPIERE_GR_IN_PROGRESS_STATUSES', 'IP'))),
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_GR_COMPLETED_STATUSES', 'CO,CL'))),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_GR_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'filter_options' => [
                ['value' => 'DR', 'label' => 'Draft'],
                ['value' => 'IP', 'label' => 'In Progress'],
                ['value' => 'CO', 'label' => 'Completed'],
                ['value' => 'CL', 'label' => 'Closed'],
                ['value' => 'VO', 'label' => 'Voided'],
            ],
            'badge_classes' => [
                'Completed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'Closed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'In Progress' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Approved' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Voided' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                'Reversed' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                'Not Approved' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
            ],
            'header_badge_classes' => [
                'NA' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'VO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'RE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'CO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'CL' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'AP' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'IP' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'DR' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            ],
            'labels' => [
                'DR' => 'Draft',
                'IP' => 'In Progress',
                'CO' => 'Completed',
                'CL' => 'Closed',
                'VO' => 'Voided',
                'RE' => 'Reversed',
                'AP' => 'Approved',
                'NA' => 'Not Approved',
                'IN' => 'Invalid',
            ],
        ],
        'limits' => [
            'list_per_page' => (int) env('IDEMPIERE_GR_LIST_PER_PAGE', 10),
            'line_default_per_page' => (int) env('IDEMPIERE_GR_LINE_DEFAULT_PER_PAGE', 10),
            'journals_per_page' => (int) env('IDEMPIERE_GR_JOURNALS_PER_PAGE', 10),
            'products_per_page' => (int) env('IDEMPIERE_GR_PRODUCTS_PER_PAGE', 25),
            'vendor_search' => (int) env('IDEMPIERE_GR_VENDOR_LIMIT', 200),
            'po_modal' => (int) env('IDEMPIERE_GR_PO_MODAL_LIMIT', 100),
        ],
        'workflow' => [
            'table_name' => env('IDEMPIERE_GR_WORKFLOW_TABLE_NAME', 'M_InOut'),
            'allowed_actions' => array_map('trim', explode(',', env('IDEMPIERE_GR_ALLOWED_ACTIONS', 'CO,PR,VO,CL,RC,RE'))),
            'complete_from' => array_map('trim', explode(',', env('IDEMPIERE_GR_COMPLETE_FROM', 'DR,IN'))),
            'void_from' => array_map('trim', explode(',', env('IDEMPIERE_GR_VOID_FROM', 'DR'))),
            'reverse_from' => array_map('trim', explode(',', env('IDEMPIERE_GR_REVERSE_FROM', 'CO'))),
            'close_from' => array_map('trim', explode(',', env('IDEMPIERE_GR_CLOSE_FROM', 'CO'))),
            'reactivate_action' => env('IDEMPIERE_GR_REACTIVATE_ACTION', 'RE'),
            'reactivate_from' => array_map('trim', explode(',', env('IDEMPIERE_GR_REACTIVATE_FROM', 'CO'))),
            'action_labels' => [
                'CO' => 'Complete',
                'PR' => 'Prepare',
                'VO' => 'Void',
                'CL' => 'Close',
                'RC' => 'Reverse',
                'RE' => 'Re-Active',
            ],
            'confirmation_messages' => [
                'CO' => 'Are you sure you want to complete this receipt?',
                'PR' => 'Are you sure you want to prepare this receipt?',
                'VO' => 'Are you sure you want to void this receipt? This action cannot be undone!',
                'CL' => 'Are you sure you want to close this receipt?',
                'RC' => 'Are you sure you want to reverse this completed receipt?',
                'RE' => 'Are you sure you want to re-active this receipt? The system will copy the document, reverse the old one, and open the new draft document.',
            ],
            'button_descriptions' => [
                'CO' => 'Process and complete this receipt',
                'VO' => 'Void this receipt document',
                'RC' => 'Reverse this completed receipt',
                'CL' => 'Close this receipt document',
                'RE' => 'Create a new draft by copying this receipt, then reverse the old document',
            ],
        ],
        'journals' => [
            'table_id' => (int) env('IDEMPIERE_GR_JOURNAL_TABLE_ID', 319),
        ],
        'purchase_order' => [
            'doc_statuses' => array_map('trim', explode(',', env('IDEMPIERE_GR_PO_DOC_STATUSES', 'CO,IP'))),
            'is_so_trx' => env('IDEMPIERE_GR_PO_IS_SO_TRX', 'N'),
        ],
    ],

    'ap-invoice' => [
        'doc_types' => [
            'base_type' => env('IDEMPIERE_DOC_BASE_TYPE_AP_INVOICE', 'API'),
        ],
        'defaults' => [
            'is_so_trx' => env('IDEMPIERE_AP_INVOICE_IS_SO_TRX', 'N'),
            'currency_iso_code' => env('IDEMPIERE_AP_INVOICE_DEFAULT_CURRENCY', 'IDR'),
            'price_list_is_so_price_list' => env('IDEMPIERE_AP_INVOICE_PRICE_LIST_IS_SO', 'N'),
            'document_status_label' => env('IDEMPIERE_AP_INVOICE_DEFAULT_STATUS_LABEL', 'Draft'),
        ],
        'statuses' => [
            'draft' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_DRAFT_STATUSES', 'DR'))),
            'in_progress' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_IN_PROGRESS_STATUSES', 'IP'))),
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_COMPLETED_STATUSES', 'CO,CL'))),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'filter_options' => [
                ['value' => 'DR', 'label' => 'Draft'],
                ['value' => 'IP', 'label' => 'In Progress'],
                ['value' => 'CO', 'label' => 'Completed'],
                ['value' => 'CL', 'label' => 'Closed'],
                ['value' => 'VO', 'label' => 'Voided'],
            ],
            'badge_classes' => [
                'Completed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'Closed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'In Progress' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Approved' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Voided' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                'Reversed' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                'Not Approved' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
            ],
            'header_badge_classes' => [
                'NA' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'VO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'RE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'CO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'CL' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'AP' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'IP' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'DR' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            ],
            'labels' => [
                'DR' => 'Draft',
                'IP' => 'In Progress',
                'CO' => 'Completed',
                'CL' => 'Closed',
                'VO' => 'Voided',
                'RE' => 'Reversed',
                'NA' => 'Not Approved',
                'IN' => 'Invalid',
                'AP' => 'Approved',
            ],
        ],
        'limits' => [
            'list_per_page' => (int) env('IDEMPIERE_AP_INVOICE_LIST_PER_PAGE', 10),
            'line_default_per_page' => (int) env('IDEMPIERE_AP_INVOICE_LINE_DEFAULT_PER_PAGE', 10),
            'line_per_page_options' => array_map('intval', array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_LINE_PER_PAGE_OPTIONS', '10,25,50')))),
            'journals_per_page' => (int) env('IDEMPIERE_AP_INVOICE_JOURNALS_PER_PAGE', 10),
            'products_per_page' => (int) env('IDEMPIERE_AP_INVOICE_PRODUCTS_PER_PAGE', 20),
            'vendor_search' => (int) env('IDEMPIERE_AP_INVOICE_VENDOR_LIMIT', 200),
            'receipt_modal' => (int) env('IDEMPIERE_AP_INVOICE_RECEIPT_MODAL_LIMIT', 15),
            'gr_modal' => (int) env('IDEMPIERE_AP_INVOICE_GR_MODAL_LIMIT', 20),
            'link_min_search_length' => (int) env('IDEMPIERE_AP_INVOICE_LINK_MIN_SEARCH_LENGTH', 2),
        ],
        'workflow' => [
            'table_name' => env('IDEMPIERE_AP_INVOICE_WORKFLOW_TABLE_NAME', 'C_Invoice'),
            'allowed_actions' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_ALLOWED_ACTIONS', 'CO,PR,VO,CL,RC'))),
            'complete_from' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_COMPLETE_FROM', 'DR,IN'))),
            'void_from' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_VOID_FROM', 'DR'))),
            'reverse_from' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_REVERSE_FROM', 'CO'))),
            'close_from' => array_map('trim', explode(',', env('IDEMPIERE_AP_INVOICE_CLOSE_FROM', 'CO'))),
            'action_labels' => [
                'CO' => 'Complete',
                'PR' => 'Prepare',
                'VO' => 'Void',
                'CL' => 'Close',
                'RC' => 'Reverse',
            ],
            'confirmation_messages' => [
                'CO' => 'Are you sure you want to complete this invoice?',
                'PR' => 'Are you sure you want to prepare this invoice?',
                'VO' => 'Are you sure you want to void this invoice? This action cannot be undone!',
                'CL' => 'Are you sure you want to close this invoice?',
                'RC' => 'Are you sure you want to reverse this completed invoice?',
            ],
            'button_descriptions' => [
                'CO' => 'Process and complete this invoice',
                'VO' => 'Void this invoice document',
                'RC' => 'Reverse this completed invoice',
                'CL' => 'Close this invoice document',
            ],
        ],
        'journals' => [
            'table_id' => (int) env('IDEMPIERE_AP_INVOICE_JOURNAL_TABLE_ID', 318),
        ],
        'receipt_filters' => [
            'doc_status' => env('IDEMPIERE_AP_INVOICE_RECEIPT_DOC_STATUS', 'CO'),
            'movement_type' => env('IDEMPIERE_AP_INVOICE_RECEIPT_MOVEMENT_TYPE', 'V+'),
            'is_so_trx' => env('IDEMPIERE_AP_INVOICE_RECEIPT_IS_SO_TRX', 'N'),
        ],
    ],

    'ap-payment' => [
        'doc_types' => [
            'payment' => (int) env('IDEMPIERE_DOC_TYPE_AP_PAYMENT_ID', 1000009),
            'base_type' => env('IDEMPIERE_DOC_BASE_TYPE_AP_PAYMENT', 'APP'),
        ],
        'defaults' => [
            'is_receipt' => env('IDEMPIERE_AP_PAYMENT_IS_RECEIPT', 'N'),
            'currency_iso_code' => env('IDEMPIERE_AP_PAYMENT_DEFAULT_CURRENCY', 'IDR'),
            'payment_rule' => env('IDEMPIERE_AP_PAYMENT_DEFAULT_PAYMENT_RULE', 'T'),
            'document_status_label' => env('IDEMPIERE_AP_PAYMENT_DEFAULT_STATUS_LABEL', 'Draft'),
            'status_filters' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_DEFAULT_STATUSES', 'DR,CO'))),
            'client_name' => env('IDEMPIERE_AP_PAYMENT_DEFAULT_CLIENT_NAME', 'Dharmamulia Prima Karya'),
        ],
        'statuses' => [
            'draft' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_DRAFT_STATUSES', 'DR,IN'))),
            'in_progress' => env('IDEMPIERE_AP_PAYMENT_IN_PROGRESS_STATUS', 'IP'),
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_COMPLETED_STATUSES', 'CO,CL'))),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'filter_options' => [
                ['value' => 'DR', 'label' => 'Draft (DR)'],
                ['value' => 'CO', 'label' => 'Complete (CO)'],
                ['value' => 'VO', 'label' => 'Void (VO)'],
                ['value' => 'RE', 'label' => 'Reverse (RE)'],
            ],
            'badge_classes' => [
                'Completed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'Closed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'In Progress' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Approved' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Voided' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                'Reversed' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                'Not Approved' => 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400',
            ],
            'header_badge_classes' => [
                'NA' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'VO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'RE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                'CO' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'CL' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'AP' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'IP' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'DR' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            ],
            'labels' => [
                'DR' => 'Draft',
                'IP' => 'In Progress',
                'CO' => 'Completed',
                'CL' => 'Closed',
                'VO' => 'Voided',
                'RE' => 'Reversed',
                'NA' => 'Not Approved',
                'IN' => 'Invalid',
                'AP' => 'Approved',
            ],
        ],
        'limits' => [
            'vendor_search' => (int) env('IDEMPIERE_AP_PAYMENT_VENDOR_LIMIT', 200),
            'list_per_page' => (int) env('IDEMPIERE_AP_PAYMENT_LIST_PER_PAGE', 10),
            'journals_per_page' => (int) env('IDEMPIERE_AP_PAYMENT_JOURNALS_PER_PAGE', 10),
            'open_invoices_per_page' => (int) env('IDEMPIERE_AP_PAYMENT_OPEN_INVOICES_PER_PAGE', 20),
            'open_invoice_search_min_chars' => (int) env('IDEMPIERE_AP_PAYMENT_OPEN_INVOICE_SEARCH_MIN_CHARS', 3),
        ],
        'workflow' => [
            'table_name' => env('IDEMPIERE_AP_PAYMENT_WORKFLOW_TABLE_NAME', 'C_Payment'),
            'allowed_actions' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_ALLOWED_ACTIONS', 'CO,VO,CL,RC'))),
            'complete_from' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_COMPLETE_FROM', 'DR,IN'))),
            'void_from' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_VOID_FROM', 'DR'))),
            'reverse_from' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_REVERSE_FROM', 'CO'))),
            'close_from' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_CLOSE_FROM', 'CO'))),
            'action_labels' => [
                'CO' => 'Complete',
                'VO' => 'Void',
                'RC' => 'Reverse',
                'CL' => 'Close',
            ],
            'confirmation_messages' => [
                'CO' => 'Are you sure you want to complete this payment?',
                'VO' => 'Are you sure you want to void this payment?',
                'RC' => 'Are you sure you want to reverse this payment?',
                'CL' => 'Are you sure you want to close this payment?',
            ],
            'button_descriptions' => [
                'CO' => 'Process and complete this payment',
                'VO' => 'Void this payment document',
                'RC' => 'Reverse this completed payment',
                'CL' => 'Close this payment document',
            ],
        ],
        'journals' => [
            'table_id' => (int) env('IDEMPIERE_AP_PAYMENT_JOURNAL_TABLE_ID', 335),
        ],
        'payment_rules' => [
            ['id' => 'T', 'text' => 'Account (Transfer)'],
            ['id' => 'X', 'text' => 'Cash'],
            ['id' => 'K', 'text' => 'Check'],
            ['id' => 'D', 'text' => 'Direct Debit'],
            ['id' => 'C', 'text' => 'Credit Card'],
            ['id' => 'A', 'text' => 'Direct Deposit'],
        ],
        'open_invoice_filters' => [
            'is_so_trx' => env('IDEMPIERE_AP_PAYMENT_OPEN_INVOICE_IS_SO_TRX', 'N'),
            'doc_statuses' => array_map('trim', explode(',', env('IDEMPIERE_AP_PAYMENT_OPEN_INVOICE_DOC_STATUSES', 'CO,CL'))),
        ],
    ],

    'sales-order' => [
        'doc_types' => [
            'target' => (int) env('IDEMPIERE_DOC_TYPE_SALES_ORDER_TARGET_ID', 1000032),
        ],
        'defaults' => [
            'is_so_trx' => env('IDEMPIERE_SALES_ORDER_IS_SO_TRX', 'Y'),
            'price_precision' => (int) env('IDEMPIERE_SALES_ORDER_PRICE_PRECISION', 2),
            'document_status_label' => env('IDEMPIERE_SALES_ORDER_DEFAULT_STATUS_LABEL', 'Drafted'),
        ],
        'filters' => [
            'is_sales_price_list' => env('IDEMPIERE_SALES_ORDER_IS_SO_PRICE_LIST', 'Y'),
            'is_customer' => env('IDEMPIERE_SALES_ORDER_IS_CUSTOMER', 'Y'),
            'is_sold' => env('IDEMPIERE_SALES_ORDER_IS_SOLD', 'Y'),
        ],
        'statuses' => [
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_COMPLETED_STATUSES', 'CO,CL'))),
            'draft' => array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_DRAFT_STATUSES', 'DR,IN'))),
            'in_progress' => env('IDEMPIERE_SALES_ORDER_IN_PROGRESS_STATUS', 'IP'),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'printable' => array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_PRINTABLE_STATUSES', 'IP,CO'))),
            'line_editable' => array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_LINE_EDITABLE_STATUSES', 'DR,IN,IP'))),
            'filter_options' => [
                ['value' => 'DR', 'label' => 'Drafted'],
                ['value' => 'CO', 'label' => 'Completed'],
                ['value' => 'IP', 'label' => 'In Progress'],
                ['value' => 'CL', 'label' => 'Closed'],
                ['value' => 'IN', 'label' => 'Invalid'],
            ],
            'badge_classes' => [
                'Completed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'Closed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'In Progress' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Approved' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
            ],
        ],
        'workflow' => [
            'allowed_actions' => array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_ALLOWED_ACTIONS', 'CO,PR,VO,CL,RE'))),
            'reactivate_action' => env('IDEMPIERE_SALES_ORDER_REACTIVATE_ACTION', 'RE'),
            'complete_action' => env('IDEMPIERE_SALES_ORDER_COMPLETE_ACTION', 'CO'),
            'reactivate_from' => array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_REACTIVATE_FROM', 'CO'))),
            'complete_from' => array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_COMPLETE_FROM', 'DR,IP'))),
            'action_labels' => [
                'CO' => 'Complete',
                'RE' => 'Re-Active',
            ],
            'confirmation_messages' => [
                'CO' => 'Are you sure you want to complete this document?',
                'RE' => 'Are you sure you want to re-activate this document?',
            ],
        ],
        'limits' => [
            'list_per_page' => (int) env('IDEMPIERE_SALES_ORDER_LIST_PER_PAGE', 10),
            'line_default_per_page' => (int) env('IDEMPIERE_SALES_ORDER_LINE_DEFAULT_PER_PAGE', 10),
            'line_per_page_options' => array_map('intval', array_map('trim', explode(',', env('IDEMPIERE_SALES_ORDER_LINE_PER_PAGE_OPTIONS', '10,25,50,100')))),
            'line_increment' => (int) env('IDEMPIERE_SALES_ORDER_LINE_INCREMENT', 10),
            'products_per_page' => (int) env('IDEMPIERE_SALES_ORDER_PRODUCTS_PER_PAGE', 25),
        ],
    ],

    'delivery-schedule' => [
        'doc_types' => [
            'target' => (int) env('IDEMPIERE_DOC_TYPE_DELIVERY_SCHEDULE_TARGET_ID', 1000051),
            'source_sales_order' => (int) env('IDEMPIERE_DOC_TYPE_DELIVERY_SCHEDULE_SOURCE_SO_ID', 1000029),
        ],
        'defaults' => [
            'is_so_trx' => env('IDEMPIERE_DELIVERY_SCHEDULE_IS_SO_TRX', 'Y'),
            'price_precision' => (int) env('IDEMPIERE_DELIVERY_SCHEDULE_PRICE_PRECISION', 2),
            'source_doc_status' => env('IDEMPIERE_DELIVERY_SCHEDULE_SOURCE_DOC_STATUS', 'CO'),
            'document_status_label' => env('IDEMPIERE_DELIVERY_SCHEDULE_DEFAULT_STATUS_LABEL', 'Drafted'),
        ],
        'filters' => [
            'is_sales_price_list' => env('IDEMPIERE_DELIVERY_SCHEDULE_IS_SO_PRICE_LIST', 'Y'),
            'is_customer' => env('IDEMPIERE_DELIVERY_SCHEDULE_IS_CUSTOMER', 'Y'),
            'is_sold' => env('IDEMPIERE_DELIVERY_SCHEDULE_IS_SOLD', 'Y'),
        ],
        'statuses' => [
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_COMPLETED_STATUSES', 'CO,CL'))),
            'draft' => array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_DRAFT_STATUSES', 'DR,IN'))),
            'in_progress' => env('IDEMPIERE_DELIVERY_SCHEDULE_IN_PROGRESS_STATUS', 'IP'),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'printable' => array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_PRINTABLE_STATUSES', 'IP,CO'))),
            'line_editable' => array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_LINE_EDITABLE_STATUSES', 'DR,IN,IP'))),
            'filter_options' => [
                ['value' => 'DR', 'label' => 'Drafted'],
                ['value' => 'CO', 'label' => 'Completed'],
                ['value' => 'IP', 'label' => 'In Progress'],
                ['value' => 'CL', 'label' => 'Closed'],
                ['value' => 'IN', 'label' => 'Invalid'],
            ],
            'badge_classes' => [
                'Completed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'Closed' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                'In Progress' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
                'Approved' => 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400',
            ],
        ],
        'workflow' => [
            'allowed_actions' => array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_ALLOWED_ACTIONS', 'CO,PR,VO,CL,RE'))),
            'reactivate_action' => env('IDEMPIERE_DELIVERY_SCHEDULE_REACTIVATE_ACTION', 'RE'),
            'complete_action' => env('IDEMPIERE_DELIVERY_SCHEDULE_COMPLETE_ACTION', 'CO'),
            'reactivate_from' => array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_REACTIVATE_FROM', 'CO'))),
            'complete_from' => array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_COMPLETE_FROM', 'DR,IP'))),
            'action_labels' => [
                'CO' => 'Complete',
                'RE' => 'Re-Active',
            ],
            'confirmation_messages' => [
                'CO' => 'Are you sure you want to complete this document?',
                'RE' => 'Are you sure you want to re-activate this document?',
            ],
        ],
        'limits' => [
            'list_per_page' => (int) env('IDEMPIERE_DELIVERY_SCHEDULE_LIST_PER_PAGE', 10),
            'line_default_per_page' => (int) env('IDEMPIERE_DELIVERY_SCHEDULE_LINE_DEFAULT_PER_PAGE', 10),
            'line_per_page_options' => array_map('intval', array_map('trim', explode(',', env('IDEMPIERE_DELIVERY_SCHEDULE_LINE_PER_PAGE_OPTIONS', '10,25,50,100')))),
            'products_per_page' => (int) env('IDEMPIERE_DELIVERY_SCHEDULE_PRODUCTS_PER_PAGE', 25),
            'sales_orders_per_page' => (int) env('IDEMPIERE_DELIVERY_SCHEDULE_SALES_ORDERS_LIMIT', 100),
            'source_lines_limit' => (int) env('IDEMPIERE_DELIVERY_SCHEDULE_SOURCE_LINES_LIMIT', 200),
            'line_increment' => (int) env('IDEMPIERE_DELIVERY_SCHEDULE_LINE_INCREMENT', 10),
        ],
    ],

    'customer-shipment' => [
        'doc_types' => [
            'shipment' => (int) env('IDEMPIERE_CUSTOMER_SHIPMENT_DOC_TYPE_ID', 1000011),
            'source_sales_order' => (int) env('IDEMPIERE_CUSTOMER_SHIPMENT_SOURCE_SO_DOC_TYPE_ID', 1000032),
        ],
        'defaults' => [
            'is_so_trx' => env('IDEMPIERE_CUSTOMER_SHIPMENT_IS_SO_TRX', 'Y'),
            'movement_type' => env('IDEMPIERE_CUSTOMER_SHIPMENT_MOVEMENT_TYPE', 'C-'),
            'delivery_via_rule' => env('IDEMPIERE_CUSTOMER_SHIPMENT_DELIVERY_VIA_RULE', 'D'),
            'freight_cost_rule' => env('IDEMPIERE_CUSTOMER_SHIPMENT_FREIGHT_COST_RULE', 'I'),
        ],
        'filters' => [
            'is_customer' => env('IDEMPIERE_CUSTOMER_SHIPMENT_IS_CUSTOMER', 'Y'),
            'is_sold' => env('IDEMPIERE_CUSTOMER_SHIPMENT_IS_SOLD', 'Y'),
            'source_sales_order_doc_status' => env('IDEMPIERE_CUSTOMER_SHIPMENT_SOURCE_SO_DOC_STATUS', 'CO'),
        ],
        'statuses' => [
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_CUSTOMER_SHIPMENT_COMPLETED_STATUSES', 'CO,CL'))),
            'draft' => array_map('trim', explode(',', env('IDEMPIERE_CUSTOMER_SHIPMENT_DRAFT_STATUSES', 'DR,IN'))),
            'in_progress' => env('IDEMPIERE_CUSTOMER_SHIPMENT_IN_PROGRESS_STATUS', 'IP'),
            'default_list' => array_map('trim', explode(',', env('IDEMPIERE_CUSTOMER_SHIPMENT_DEFAULT_LIST_STATUSES', 'DR,CO'))),
            'delivery_progress' => array_map('trim', explode(',', env('IDEMPIERE_CUSTOMER_SHIPMENT_DELIVERY_PROGRESS_STATUSES', 'DR,IP,CO'))),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_CUSTOMER_SHIPMENT_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'editable_lines' => array_map('trim', explode(',', env('IDEMPIERE_CUSTOMER_SHIPMENT_EDITABLE_LINE_STATUSES', 'DR,IN,IP'))),
            'printable' => array_map('trim', explode(',', env('IDEMPIERE_CUSTOMER_SHIPMENT_PRINTABLE_STATUSES', 'IP,CO'))),
        ],
        'limits' => [
            'products_per_page' => (int) env('IDEMPIERE_CUSTOMER_SHIPMENT_PRODUCTS_PER_PAGE', 25),
            'line_increment' => (int) env('IDEMPIERE_CUSTOMER_SHIPMENT_LINE_INCREMENT', 10),
        ],
        'journals' => [
            'table_id' => (int) env('IDEMPIERE_CUSTOMER_SHIPMENT_JOURNAL_TABLE_ID', 319),
        ],
        'references' => [
            'delivery_via_rule' => (int) env('IDEMPIERE_CUSTOMER_SHIPMENT_DELIVERY_VIA_REFERENCE_ID', 152),
            'freight_cost_rule' => (int) env('IDEMPIERE_CUSTOMER_SHIPMENT_FREIGHT_COST_REFERENCE_ID', 153),
        ],
        'rules' => [
            'shipper_delivery_via' => env('IDEMPIERE_CUSTOMER_SHIPMENT_SHIPPER_DELIVERY_VIA', 'S'),
        ],
    ],

    'ar-receipt' => [
        'doc_types' => [
            'receipt' => (int) env('IDEMPIERE_AR_RECEIPT_DOC_TYPE_ID', 1000008),
            'base_type' => env('IDEMPIERE_DOC_BASE_TYPE_AR_RECEIPT', 'ARR'),
        ],
        'defaults' => [
            'is_receipt' => env('IDEMPIERE_AR_RECEIPT_IS_RECEIPT', 'Y'),
            'active_flag' => env('IDEMPIERE_AR_RECEIPT_ACTIVE_FLAG', 'Y'),
            'currency_iso_code' => env('IDEMPIERE_AR_RECEIPT_DEFAULT_CURRENCY', 'IDR'),
            'payment_rule' => env('IDEMPIERE_AR_RECEIPT_DEFAULT_PAYMENT_RULE', 'T'),
        ],
        'filters' => [
            'is_customer' => env('IDEMPIERE_AR_RECEIPT_IS_CUSTOMER', 'Y'),
            'invoice_is_so_trx' => env('IDEMPIERE_AR_RECEIPT_INVOICE_IS_SO_TRX', 'Y'),
            'invoice_doc_statuses' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_INVOICE_DOC_STATUSES', 'CO,CL'))),
            'excluded_allocation_statuses' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_EXCLUDED_ALLOCATION_STATUSES', 'VO,RE'))),
        ],
        'statuses' => [
            'default_list' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_DEFAULT_LIST_STATUSES', 'DR,CO'))),
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_COMPLETED_STATUSES', 'CO,CL'))),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'draft' => env('IDEMPIERE_AR_RECEIPT_DRAFT_STATUS', 'DR'),
            'in_progress' => env('IDEMPIERE_AR_RECEIPT_IN_PROGRESS_STATUS', 'IP'),
            'labels' => [
                'DR' => 'Draft',
                'IP' => 'In Progress',
                'CO' => 'Completed',
                'CL' => 'Closed',
                'VO' => 'Voided',
                'RE' => 'Reversed',
                'NA' => 'Not Approved',
                'IN' => 'Invalid',
                'AP' => 'Approved',
            ],
            'filter_options' => [
                ['value' => 'DR', 'label' => 'Draft'],
                ['value' => 'CO', 'label' => 'Complete'],
                ['value' => 'VO', 'label' => 'Void'],
                ['value' => 'RE', 'label' => 'Reverse'],
            ],
        ],
        'limits' => [
            'per_page' => (int) env('IDEMPIERE_AR_RECEIPT_PER_PAGE', 10),
            'customer_search' => (int) env('IDEMPIERE_AR_RECEIPT_CUSTOMER_LIMIT', 200),
            'open_invoices_per_page' => (int) env('IDEMPIERE_AR_RECEIPT_OPEN_INVOICES_PER_PAGE', 20),
            'invoice_lookup_min_search_length' => (int) env('IDEMPIERE_AR_RECEIPT_INVOICE_LOOKUP_MIN_SEARCH_LENGTH', 3),
            'journals_per_page' => (int) env('IDEMPIERE_AR_RECEIPT_JOURNALS_PER_PAGE', 10),
        ],
        'workflow' => [
            'table_name' => env('IDEMPIERE_AR_RECEIPT_WORKFLOW_TABLE_NAME', 'C_Payment'),
            'complete_from' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_COMPLETE_FROM_STATUSES', 'DR,IP'))),
            'void_from' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_VOID_FROM_STATUSES', 'DR'))),
            'reverse_from' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_REVERSE_FROM_STATUSES', 'CO'))),
            'close_from' => array_map('trim', explode(',', env('IDEMPIERE_AR_RECEIPT_CLOSE_FROM_STATUSES', 'CO'))),
        ],
        'journals' => [
            'table_id' => (int) env('IDEMPIERE_AR_RECEIPT_JOURNAL_TABLE_ID', 335),
        ],
        'payment_rules' => [
            ['id' => 'T', 'text' => 'Account (Transfer)'],
            ['id' => 'X', 'text' => 'Cash'],
            ['id' => 'K', 'text' => 'Check'],
            ['id' => 'D', 'text' => 'Direct Debit'],
            ['id' => 'C', 'text' => 'Credit Card'],
            ['id' => 'A', 'text' => 'Direct Deposit'],
        ],
    ],

    'ar-invoice' => [
        'doc_types' => [
            'base_type' => env('IDEMPIERE_DOC_BASE_TYPE_AR_INVOICE', 'ARI'),
        ],
        'defaults' => [
            'is_so_trx' => env('IDEMPIERE_AR_INVOICE_IS_SO_TRX', 'Y'),
            'currency_iso_code' => env('IDEMPIERE_AR_INVOICE_DEFAULT_CURRENCY', 'IDR'),
        ],
        'filters' => [
            'is_customer' => env('IDEMPIERE_AR_INVOICE_IS_CUSTOMER', 'Y'),
            'price_list_is_so_price_list' => env('IDEMPIERE_AR_INVOICE_PRICE_LIST_IS_SO', 'N'),
            'product_is_purchased' => env('IDEMPIERE_AR_INVOICE_PRODUCT_IS_PURCHASED', 'Y'),
        ],
        'statuses' => [
            'default_list' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_DEFAULT_LIST_STATUSES', 'DR,CO'))),
            'completed' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_COMPLETED_STATUSES', 'CO,CL'))),
            'read_only' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_READ_ONLY_STATUSES', 'CO,CL,VO,RE'))),
            'excluded_invoiced' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_EXCLUDED_INVOICED_STATUSES', 'VO,RE'))),
            'draft' => env('IDEMPIERE_AR_INVOICE_DRAFT_STATUS', 'DR'),
            'in_progress' => env('IDEMPIERE_AR_INVOICE_IN_PROGRESS_STATUS', 'IP'),
        ],
        'limits' => [
            'customer_search' => (int) env('IDEMPIERE_AR_INVOICE_CUSTOMER_LIMIT', 200),
            'shipment_lines_per_page' => (int) env('IDEMPIERE_AR_INVOICE_SHIPMENT_LINES_PER_PAGE', 15),
            'shipment_link_per_page' => (int) env('IDEMPIERE_AR_INVOICE_SHIPMENT_LINK_PER_PAGE', 20),
            'products_per_page' => (int) env('IDEMPIERE_AR_INVOICE_PRODUCTS_PER_PAGE', 20),
            'lookup_min_search_length' => (int) env('IDEMPIERE_AR_INVOICE_LOOKUP_MIN_SEARCH_LENGTH', 2),
        ],
        'workflow' => [
            'complete_from' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_COMPLETE_FROM_STATUSES', 'DR,IP'))),
            'void_from' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_VOID_FROM_STATUSES', 'DR'))),
            'reverse_from' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_REVERSE_FROM_STATUSES', 'CO'))),
            'close_from' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_CLOSE_FROM_STATUSES', 'CO'))),
            'print_excluded' => array_map('trim', explode(',', env('IDEMPIERE_AR_INVOICE_PRINT_EXCLUDED_STATUSES', 'VO'))),
        ],
        'journals' => [
            'table_id' => (int) env('IDEMPIERE_AR_INVOICE_JOURNAL_TABLE_ID', 318),
        ],
        'shipment_filters' => [
            'doc_status' => env('IDEMPIERE_AR_INVOICE_SHIPMENT_DOC_STATUS', 'CO'),
            'movement_type' => env('IDEMPIERE_AR_INVOICE_SHIPMENT_MOVEMENT_TYPE', 'C-'),
            'is_so_trx' => env('IDEMPIERE_AR_INVOICE_SHIPMENT_IS_SO_TRX', 'Y'),
        ],
        'shipment_link_filters' => [
            'doc_status' => env('IDEMPIERE_AR_INVOICE_LINK_SHIPMENT_DOC_STATUS', 'CO'),
            'movement_type' => env('IDEMPIERE_AR_INVOICE_LINK_SHIPMENT_MOVEMENT_TYPE', 'V+'),
            'is_so_trx' => env('IDEMPIERE_AR_INVOICE_LINK_SHIPMENT_IS_SO_TRX', 'Y'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | iDempiere Role IDs
    |--------------------------------------------------------------------------
    |
    | Role IDs used to filter AD users for specific purposes.
    | Override with environment variables when needed.
    |
    */

    'roles' => [
        'driver' => (int) env('IDEMPIERE_DRIVER_ROLE_ID', 1000051),
    ],

];
