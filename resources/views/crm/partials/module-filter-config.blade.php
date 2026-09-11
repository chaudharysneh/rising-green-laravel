@php
    $select = fn ($label, $placeholder, $options) => compact('label', 'placeholder', 'options') + ['kind' => 'select'];
    $date = fn ($label) => ['label' => $label, 'kind' => 'date'];
    $text = fn ($label, $placeholder) => compact('label', 'placeholder') + ['kind' => 'text'];
    $labels = fn ($values) => collect($values)->mapWithKeys(fn ($v) => [$v => ucwords(str_replace('_', ' ', $v))])->all();
    $staff = fn () => \App\Models\User::orderBy('name')->pluck('name', 'id');
    $types = $labels(['residential', 'commercial', 'industrial']);
    $fields = match ($module) {
        'followups' => [
            'assigned_user_id' => $select('Staff', 'All staff', $staff()),
            'created_at' => $date('Created At'), 'follow_up_at' => $date('Follow Up Date'),
            'status' => $select('Status', 'All statuses', ['pending'=>'Pending','resheduled'=>'Rescheduled','completed'=>'Completed','cancelled'=>'Cancelled']),
        ],
        'meetings' => [
            'customer_id' => $select('Customer', 'All customers', \App\Models\Customer::orderBy('name')->pluck('name', 'id')),
            'assigned_user_id' => $select('Staff', 'All staff', $staff()),
            'scheduled_at' => $date('Scheduled On'),
            'meeting_type' => $select('Meeting Type', 'All types', $labels(['virtual','in-person','telephonic'])),
            'status' => $select('Status', 'All statuses', $labels(['scheduled','completed','cancelled'])),
        ],
        'bom' => [
            'category_id' => $select('Make', 'All make', \App\Models\Category::orderBy('name')->pluck('name','id')),
            'technology_id' => $select('Technology', 'All technology', \App\Models\Technology::orderBy('title')->pluck('title','id')),
            'warranty_id' => $select('Warranty', 'All warranty', \App\Models\Warranty::orderBy('title')->pluck('title','id')),
            'created_at' => $date('Created At'),
        ],
        'estimates' => [
            'estimate_no' => $text('Estimate No', 'Search estimate no'),
            'type' => $select('Estimate Type', 'All estimate type', $types),
            'estimate_date' => $date('Estimate Date'),
            'status' => $select('Status', 'All status', $labels(['pending','approved','rejected','completed'])),
        ],
        'invoices' => [
            'invoice_no' => $text('Invoice No', 'Search invoice no'),
            'type' => $select('Invoice Type', 'All invoice type', $types),
            'invoice_date' => $date('Invoice Date'), 'due_date' => $date('Due Date'),
            'status' => $select('Status', 'All status', $labels(['unpaid','paid','cancelled'])),
        ],
    };
@endphp
@include('crm.partials.list-filters')
