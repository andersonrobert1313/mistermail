<div class="sub-section">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-lg-12">
            <h2 style="color:#335eea !important" class="text-semibold">{{ trans('messages.subscription.logs') }}</h2>
            <p>{{ trans('messages.subscription.logs.intro') }}</p>


            <table class="table table-box pml-table table-log mt-10">
                <tr>
                    <th>{{ trans('messages.invoice.created_at') }}</th>
                    <th>{{ trans('messages.invoice.description') }}</th>
                    <th>{{ trans('messages.invoice.period_ends_at') }}</th>
                    <th>{{ trans('messages.invoice.amount') }}</th>
                    <th>{{ trans('messages.invoice.status') }}</th>
                    <th>{{ trans('messages.invoice.action') }}</th>
                </tr>
                @forelse ($gateway->getInvoices($subscription) as $key => $invoice)
                    <tr>
                        <td>
                            <span class="no-margin kq_search">
                                {{ Acelle\Library\Tool::formatDate(Carbon\Carbon::createFromTimestamp($invoice->createdAt)) }}
                            </span>
                        </td>
                        <td>
                            <span class="no-margin kq_search">
                                {!! $invoice->description !!}
                            </span>
                        </td>
                        <td>
                            <span class="no-margin kq_search">
                                @if ($invoice->periodEndsAt)
                                    {{ Acelle\Library\Tool::formatDate(Carbon\Carbon::createFromTimestamp($invoice->periodEndsAt)) }}
                                @else
                                    {{ trans('messages.n_a') }}
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="no-margin kq_search">
                                @if ($invoice->amount)
                                    {{ $invoice->amount }}
                                @else
                                    {{ trans('messages.n_a') }}
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="no-margin kq_search">
                                <span class="label label-success bg-{{ $invoice->status }}" style="white-space: nowrap;">
                                    {{ str_replace('_', ' ', $invoice->status) }}
                                </span>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-mc_default btn-disabled" disabled>
                                {{ trans('messages.edit') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center" colspan="5">
                            {{ trans('messages.subscription.logs.empty') }}
                        </td>
                    </te>
                @endforelse
            </table>
        </div>
    </div>
</div>