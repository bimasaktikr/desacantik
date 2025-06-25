<table class="min-w-full text-sm">
    <thead>
        <tr>
            <th class="px-2 py-1 text-left">SLS Name</th>
            <th class="px-2 py-1 text-left">Business Count</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($slsList as $sls)
            <tr>
                <td class="px-2 py-1">{{ $sls->name }}</td>
                <td class="px-2 py-1">
                    {{ $sls->businesses->count() }}
                    @if($sls->businesses->count() > 0)
                        <span class="font-bold text-green-600">✔</span>
                    @else
                        <span class="font-bold text-red-600">✘</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
