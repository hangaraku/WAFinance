{{-- 
    Cashfloo Components Index
    
    Available Components:
    
    1. monthly-summary - Monthly summary cards (income, expenses, total)
    2. navigation-tabs - Navigation tabs with horizontal scroll
    3. transaction-card - Compact transaction card for dashboard
    4. transaction-detail-card - Detailed transaction card for transactions page
    5. card - Reusable card container
    6. card-header - Card header with title and action slot
    7. quick-actions - Quick actions container with grid layout
    8. action-button - Action button with color variants
    9. empty-state - Empty state with icon, title, description and action
    10. fab - Floating action button
    11. date-header - Date header for transaction grouping
    12. budget-progress - Budget progress with progress bar
    13. goal-card - Goal card with progress tracking
    14. month-navigation - Month navigation with arrows
    15. page-header - Page header with back button and actions
    16. icon - Reusable icon component with different types
    17. top-bar - Top status bar with logo, search, notifications, and user avatar
    
    Usage Examples:
    
    <x-monthly-summary :income="$income" :expenses="$expenses" :total="$total" />
    <x-navigation-tabs active-tab="harian" />
    <x-transaction-card :transaction="$transaction" />
    <x-card>
        <x-card-header title="Recent Transactions">
            <x-slot name="action">
                <a href="#" class="text-orange-500">View All</a>
            </x-slot>
        </x-card-header>
        Content here...
    </x-card>
--}}
