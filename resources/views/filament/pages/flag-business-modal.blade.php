@php
    use Illuminate\Support\Facades\Auth;
@endphp
<div class="space-y-2">
    <div class="flex justify-between items-center">
        <div>
            <span class="font-semibold">Business Name:</span> {{ $record->name }}
        </div>
        @if(Auth::user()->roles->contains('name', 'Employee'))
            <form method="POST" action="{{ route('flag-business-field', ['id' => $record->id, 'field' => 'name_error']) }}">
                @csrf
                <button type="submit" class="ml-2">
                    <x-heroicon-o-flag :class="$record->name_error ? 'text-red-500' : 'text-gray-400'" />
                </button>
            </form>
        @endif
    </div>
    <div class="flex justify-between items-center">
        <div>
            <span class="font-semibold">Description:</span> {{ $record->description }}
        </div>
        @if(Auth::user()->roles->contains('name', 'Employee'))
            <form method="POST" action="{{ route('flag-business-field', ['id' => $record->id, 'field' => 'description_error']) }}">
                @csrf
                <button type="submit" class="ml-2">
                    <x-heroicon-o-flag :class="$record->description_error ? 'text-red-500' : 'text-gray-400'" />
                </button>
            </form>
        @endif
    </div>
    <div class="flex justify-between items-center">
        <div>
            <span class="font-semibold">Address:</span> {{ $record->address }}
        </div>
        @if(Auth::user()->roles->contains('name', 'Employee'))
            <form method="POST" action="{{ route('flag-business-field', ['id' => $record->id, 'field' => 'address_error']) }}">
                @csrf
                <button type="submit" class="ml-2">
                    <x-heroicon-o-flag :class="$record->address_error ? 'text-red-500' : 'text-gray-400'" />
                </button>
            </form>
        @endif
    </div>
    <div class="flex justify-between items-center">
        <div>
            <span class="font-semibold">Category:</span> {{ optional($record->businessCategory)->code }} - {{ optional($record->businessCategory)->description }}
        </div>
        @if(Auth::user()->roles->contains('name', 'Employee'))
            <form method="POST" action="{{ route('flag-business-field', ['id' => $record->id, 'field' => 'business_category_id_error']) }}">
                @csrf
                <button type="submit" class="ml-2">
                    <x-heroicon-o-flag :class="$record->business_category_id_error ? 'text-red-500' : 'text-gray-400'" />
                </button>
            </form>
        @endif
    </div>
</div>
