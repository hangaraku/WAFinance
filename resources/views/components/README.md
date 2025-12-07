# Cashfloo Components

This directory contains reusable Blade components for the Cashfloo application. All components are designed to be mobile-first and follow consistent design patterns.

## Available Components

### 1. monthly-summary
Monthly summary cards showing income, expenses, and total balance.

**Props:**
- `income` (number) - Total income amount
- `expenses` (number) - Total expenses amount  
- `total` (number) - Net balance (income - expenses)

**Usage:**
```blade
<x-monthly-summary :income="$monthlySummary['income']" :expenses="$monthlySummary['expenses']" :total="$monthlySummary['total']" />
```

### 2. navigation-tabs
Horizontal scrollable navigation tabs for different views.

**Props:**
- `activeTab` (string) - Currently active tab (default: 'harian')

**Usage:**
```blade
<x-navigation-tabs active-tab="harian" />
```

### 3. transaction-card
Compact transaction card for dashboard display.

**Props:**
- `transaction` (Transaction model) - Transaction to display

**Usage:**
```blade
<x-transaction-card :transaction="$transaction" />
```

### 4. transaction-detail-card
Detailed transaction card for transactions page.

**Props:**
- `transaction` (Transaction model) - Transaction to display

**Usage:**
```blade
<x-transaction-detail-card :transaction="$transaction" />
```

### 5. card
Reusable card container with consistent styling.

**Props:**
- `class` (string) - Additional CSS classes

**Slots:**
- `default` - Card content

**Usage:**
```blade
<x-card>
    <div>Card content here</div>
</x-card>
```

### 6. card-header
Card header with title and optional action slot.

**Props:**
- `title` (string) - Header title

**Slots:**
- `action` - Optional action button/link

**Usage:**
```blade
<x-card-header title="Recent Transactions">
    <x-slot name="action">
        <a href="#" class="text-orange-500">View All</a>
    </x-slot>
</x-card-header>
```

### 7. quick-actions
Container for quick action buttons in grid layout.

**Props:**
- `title` (string) - Section title (default: 'Aksi Cepat')

**Slots:**
- `default` - Action buttons

**Usage:**
```blade
<x-quick-actions>
    <x-action-button href="{{ route('transaction.new') }}" color="orange">
        <x-slot name="icon">
            <x-icon type="plus" />
        </x-slot>
        <x-slot name="text">Tambah Pemasukan</x-slot>
    </x-action-button>
</x-quick-actions>
```

### 8. action-button
Action button with color variants and icon/text slots.

**Props:**
- `href` (string) - Button link
- `color` (string) - Button color (orange, red, green, blue)

**Slots:**
- `icon` - Button icon
- `text` - Button text

**Usage:**
```blade
<x-action-button href="{{ route('transaction.new') }}" color="red">
    <x-slot name="icon">
        <x-icon type="minus" />
    </x-slot>
    <x-slot name="text">Tambah Pengeluaran</x-slot>
</x-action-button>
```

### 9. empty-state
Empty state display with icon, title, description and action.

**Props:**
- `icon` (string) - Icon to display
- `title` (string) - Empty state title
- `description` (string) - Empty state description

**Slots:**
- `action` - Optional action button

**Usage:**
```blade
<x-empty-state icon="<x-icon type='transaction' />" title="Belum ada transaksi" description="Mulai dengan menambahkan transaksi pertama Anda">
    <x-slot name="action">
        <a href="{{ route('transaction.new') }}" class="btn-primary">Tambah Transaksi</a>
    </x-slot>
</x-empty-state>
```

### 10. fab
Floating action button.

**Props:**
- `href` (string) - Button link
- `color` (string) - Button color (orange, red, green, blue)

**Slots:**
- `default` - Button icon

**Usage:**
```blade
<x-fab href="{{ route('transaction.new') }}" color="orange">
    <x-icon type="plus" class="w-6 h-6" />
</x-fab>
```

### 11. date-header
Date header for transaction grouping.

**Props:**
- `date` (string) - Date string
- `income` (number) - Daily income total
- `expense` (number) - Daily expense total

**Usage:**
```blade
<x-date-header :date="$date" :income="$dayIncome" :expense="$dayExpense" />
```

### 12. budget-progress
Budget progress display with progress bar.

**Props:**
- `budget` (Budget model) - Budget to display
- `spent` (number) - Amount spent

**Usage:**
```blade
<x-budget-progress :budget="$budget" :spent="$spent" />
```

### 13. goal-card
Goal card with progress tracking.

**Props:**
- `goal` (Goal model) - Goal to display

**Usage:**
```blade
<x-goal-card :goal="$goal" />
```

### 14. month-navigation
Month navigation with left/right arrows.

**Props:**
- `currentMonth` (number) - Current month (1-12)
- `currentYear` (number) - Current year

**Usage:**
```blade
<x-month-navigation :current-month="$currentMonth" :current-year="$currentYear" />
```

### 15. page-header
Page header with back button and actions.

**Props:**
- `title` (string) - Page title
- `showBack` (boolean) - Show back button (default: false)
- `backUrl` (string) - Back button URL (default: '#')

**Slots:**
- `actions` - Optional action buttons

**Usage:**
```blade
<x-page-header title="Transactions" :show-back="true" back-url="{{ route('stats') }}">
    <x-slot name="actions">
        <button class="btn-primary">Filter</button>
    </x-slot>
</x-page-header>
```

### 16. icon
Reusable icon component with different types.

**Props:**
- `type` (string) - Icon type
- `class` (string) - CSS classes (default: 'w-5 h-5')

**Available Types:**
- `plus` - Plus icon
- `minus` - Minus icon
- `arrow-left` - Left arrow
- `arrow-right` - Right arrow
- `chevron-left` - Left chevron
- `chevron-right` - Right chevron
- `transaction` - Transaction icon
- `budget` - Budget icon
- `goal` - Goal/checkmark icon

**Usage:**
```blade
<x-icon type="plus" class="w-6 h-6" />
```

## Component Design Principles

1. **Mobile-First**: All components are designed for mobile devices first
2. **Consistent Spacing**: Uses consistent spacing scale (2, 3, 4, 6, 8, 12, 16, 24)
3. **Reusable**: Components are designed to be reused across different views
4. **Slot-Based**: Uses Blade slots for flexible content injection
5. **Props Validation**: Props are clearly defined and validated
6. **Accessibility**: Components follow accessibility best practices

## Adding New Components

When adding new components:

1. Create the component file in `resources/views/components/`
2. Use consistent naming convention (kebab-case)
3. Document all props and slots
4. Add to this README
5. Update the index.blade.php file
6. Test on mobile devices

## Best Practices

1. **Keep Components Small**: Each component should have a single responsibility
2. **Use Slots**: Use slots for flexible content injection
3. **Consistent Props**: Use consistent prop naming across components
4. **Mobile Optimization**: Ensure components work well on small screens
5. **Documentation**: Always document props, slots, and usage examples
