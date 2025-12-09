// assets/js/admin/user-management-datatables.js
import $ from 'jquery';
import 'datatables.net';

function initDataTable(tableId, options = {}) {
    if ($.fn.dataTable.isDataTable(tableId)) {
        $(tableId).DataTable().destroy();
    }
    
    if ($(tableId).length) {
        $(tableId).DataTable({
            pageLength: 10,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            ordering: true,
            order: options.order || [[0, 'asc']],
            dom: "<'flex justify-between items-center mb-2'<'flex items-center'l><'flex items-center'f>>rt<'flex justify-between items-center mt-2'<'flex items-center'i><'flex items-center'p>>",
            language: options.language || {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "Show _MENU_",
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
            initComplete: function(settings, json) {
                const searchInput = $('.dataTables_filter input');
                searchInput.addClass('form-control');
                searchInput.css({
                    'width': '250px',
                    'display': 'inline-block'
                });
                
                const lengthSelect = $('.dataTables_length select');
                lengthSelect.addClass('form-control');
                lengthSelect.css({
                    'width': 'auto',
                    'display': 'inline-block'
                });
                
                // Call custom initComplete if provided
                if (options.initComplete && typeof options.initComplete === 'function') {
                    options.initComplete.call(this, settings, json);
                }
            }
        });
    }
}

// Function to add activity log filters with form-control classes
function addActivityLogFilters(api) {
    // Add form-control classes to filter inputs
    $('#userFilter').addClass('form-control');
    $('#actionFilter').addClass('form-control');
    $('#roleFilter').addClass('form-control');
    $('#dateFilter').addClass('form-control');
    $('#clearFilters').addClass('btn btn-sm btn-outline-secondary');
    
    // User filter
    $('#userFilter').on('keyup', function() {
        api.column(1).search(this.value).draw();
    });
    
    // Action filter
    $('#actionFilter').on('change', function() {
        var val = this.value;
        if (val === '') {
            api.column(2).search('').draw();
        } else {
            api.column(2).search('^' + val, true, false).draw();
        }
    });
    
    // Role filter
    $('#roleFilter').on('change', function() {
        var val = this.value;
        if (val === '') {
            api.column(4).search('').draw();
        } else {
            api.column(4).search('^' + val, true, false).draw();
        }
    });
    
    // Date filter
    $('#dateFilter').on('change', function() {
        var val = this.value;
        if (val) {
            api.column(0).search(val).draw();
        } else {
            api.column(0).search('').draw();
        }
    });
    
    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#userFilter').val('');
        $('#actionFilter').val('');
        $('#roleFilter').val('');
        $('#dateFilter').val('');
        api.columns().search('').draw();
        api.search('').draw();
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // User Management Table
    initDataTable('#userManagementTable', {
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
    
    // Listing Management Table
    initDataTable('#listingManagementTable', {
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
    
    // Collectible Management Table
    initDataTable('#collectibleManagementTable', {
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
    
    // Activity Logs Table - FIXED VERSION
    initDataTable('#activityLogsTable', {
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search logs...",
            lengthMenu: "Show _MENU_ logs",
            info: "Showing _START_ to _END_ of _TOTAL_ logs",
            zeroRecords: "No matching logs found",
            infoEmpty: "No logs available",
            emptyTable: "<div class='datatable-empty-state'><div class='datatable-empty-icon'>📭</div><p>No activity logs found</p></div>",
            infoFiltered: "(filtered from _MAX_ total logs)",
            paginate: {
                first: '«',
                last: '»',
                next: '›',
                previous: '‹'
            }
        },
        order: [[0, 'desc']], // Sort by timestamp descending
        columnDefs: [
            { 
                targets: 0, // Timestamp column - FIXED DATE SORTING
                type: 'date',
                render: function(data, type, row) {
                    if (type === 'sort' || type === 'filter') {
                        // Extract ISO date from data-order attribute
                        if (typeof data === 'string' && data.includes('data-order=')) {
                            // Extract from data-order attribute
                            var orderMatch = data.match(/data-order="([^"]+)"/);
                            if (orderMatch) {
                                return orderMatch[1]; // Return ISO date for sorting
                            }
                        }
                        
                        // Try to extract date from text
                        var temp = document.createElement('div');
                        temp.innerHTML = data;
                        var dateText = temp.textContent.trim();
                        
                        // Try different date formats
                        // Format 1: ISO (2024-12-08 15:30:25)
                        var isoMatch = dateText.match(/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/);
                        if (isoMatch) {
                            return isoMatch[1];
                        }
                        
                        // Format 2: Dec 8, 2024 3:30 PM
                        var dateObj = new Date(dateText);
                        if (!isNaN(dateObj.getTime())) {
                            // Convert to ISO format for sorting
                            return dateObj.toISOString();
                        }
                        
                        // If all else fails, return raw text
                        return dateText;
                    }
                    return data;
                }
            },
            { 
                targets: 1, // User column (contains HTML)
                render: function(data, type, row) {
                    if (type === 'sort' || type === 'filter') {
                        // Extract username from HTML for sorting/filtering
                        if (typeof data === 'string') {
                            var temp = document.createElement('div');
                            temp.innerHTML = data;
                            var username = $(temp).find('strong').text().trim();
                            return username || data.replace(/<[^>]*>/g, '').trim();
                        }
                        return data;
                    }
                    return data;
                }
            },
            { 
                targets: 2, // Action badges
                render: function(data, type, row) {
                    if (type === 'sort' || type === 'filter') {
                        // Extract action text from badge
                        if (typeof data === 'string') {
                            var temp = document.createElement('div');
                            temp.innerHTML = data;
                            var action = $(temp).text().trim().replace(/\s+/g, ' ');
                            return action || data.replace(/<[^>]*>/g, '').trim();
                        }
                        return data;
                    }
                    return data;
                }
            },
            { 
                targets: 3, // Details column
                render: function(data, type, row) {
                    if (type === 'sort' || type === 'filter') {
                        // Extract text from truncated span
                        if (typeof data === 'string') {
                            var temp = document.createElement('div');
                            temp.innerHTML = data;
                            var text = $(temp).text().trim();
                            return text || data.replace(/<[^>]*>/g, '').trim();
                        }
                        return data;
                    }
                    return data;
                }
            },
            { 
                targets: 4, // Role badges
                render: function(data, type, row) {
                    if (type === 'sort' || type === 'filter') {
                        // Extract role from badge
                        if (typeof data === 'string') {
                            var temp = document.createElement('div');
                            temp.innerHTML = data;
                            var role = $(temp).text().trim();
                            return role || data.replace(/<[^>]*>/g, '').trim();
                        }
                        return data;
                    }
                    return data;
                }
            }
        ],
        initComplete: function(settings, json) {
            const api = this.api();
            
            // Add custom filters for activity logs
            addActivityLogFilters(api);
            
            // Update log count
            $('#logCount').text(api.rows().count() + ' entries');
            
            // Update count on draw
            api.on('draw', function() {
                $('#logCount').text(api.rows({search:'applied'}).count() + ' entries');
            });
            
            // Debug: Check what DataTables is seeing for sorting
            console.log('Activity Logs DataTable initialized');
            console.log('Initial sort order:', api.order());
            
            // Optional: Force re-sort to ensure proper order
            api.draw();
        }
    });
});