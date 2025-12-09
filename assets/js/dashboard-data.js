import $ from 'jquery';
import 'datatables.net';

function initDataTable(tableId, options = {}) {
    if ($.fn.dataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
    }
    
    if ($(tableId).length) {
        $(tableId).DataTable({
            pageLength: 5, // Show only 5 rows for dashboard tables
            lengthChange: false, // Hide length change for dashboard
            lengthMenu: false, // Disable length menu
            ordering: true,
            order: [[0, 'asc']], // Sort by ID descending (newest first)
            dom: "<'flex justify-between items-center mb-2'<'flex items-center'f>>rt<'flex justify-between items-center mt-2'<'flex items-center'i><'flex items-center'p>>",
            language: options.language || {
                search: "_INPUT_",
                searchPlaceholder: options.searchPlaceholder || "Search...",
                info: "Showing _START_ to _END_ of _TOTAL_",
                zeroRecords: "No matching records found",
                infoEmpty: "No records available",
                emptyTable: options.emptyTable || "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No data available</p></div>",
                infoFiltered: "(filtered from _MAX_ total)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: options.columnDefs || [],
            initComplete: function() {
                const searchInput = $('.dataTables_filter input');
                searchInput.addClass('form-control');
                searchInput.css({
                    'width': '200px',
                    'display': 'inline-block'
                });
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // User Management Table
    if ($('#userManagementTable').length) {
        initDataTable('#userManagementTable', {
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users...",
                lengthMenu: "Show _MENU_ users",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                zeroRecords: "No matching users found",
                infoEmpty: "No users available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No users found</p></div>",
                infoFiltered: "(filtered from _MAX_ total users)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 1, // Profile image column
                    searchable: false,
                    orderable: false
                },
                { 
                    targets: 6, // Actions column
                    searchable: false,
                    orderable: false
                }
            ]
        });
    }
    
    // Listing Management Table
    if ($('#listingManagementTable').length) {
        initDataTable('#listingManagementTable', {
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search listings...",
                lengthMenu: "Show _MENU_ listings",
                info: "Showing _START_ to _END_ of _TOTAL_ listings",
                zeroRecords: "No matching listings found",
                infoEmpty: "No listings available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No listings found</p></div>",
                infoFiltered: "(filtered from _MAX_ total listings)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 4, // Actions column
                    searchable: false,
                    orderable: false
                }
            ]
        });
    }
    
    // Collectible Management Table
    if ($('#collectibleManagementTable').length) {
        initDataTable('#collectibleManagementTable', {
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search collectibles...",
                lengthMenu: "Show _MENU_ collectibles",
                info: "Showing _START_ to _END_ of _TOTAL_ collectibles",
                zeroRecords: "No matching collectibles found",
                infoEmpty: "No collectibles available",
                emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No collectibles found</p></div>",
                infoFiltered: "(filtered from _MAX_ total collectibles)",
                paginate: {
                    first: '«',
                    last: '»',
                    next: '›',
                    previous: '‹'
                }
            },
            columnDefs: [
                { 
                    targets: 1, // Image column
                    searchable: false,
                    orderable: false
                },
                { 
                    targets: 5, // Actions column
                    searchable: false,
                    orderable: false
                }
            ]
        });
    }
    
    // Dashboard Tables (Recent Listings)
    if ($('#recentListingsTable').length) {
        initDataTable('#recentListingsTable', {
            searchPlaceholder: "Search listings...",
            emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No recent listings</p></div>",
            columnDefs: [
                { 
                    orderable: true,
                    targets: '_all'
                }
            ]
        });
    }
    
    // Dashboard Tables (Recent Users)
    if ($('#recentUsersTable').length) {
        initDataTable('#recentUsersTable', {
            searchPlaceholder: "Search users...",
            emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No recent users</p></div>",
            columnDefs: [
                { 
                    orderable: true,
                    targets: '_all'
                }
            ]
        });
    }
});
