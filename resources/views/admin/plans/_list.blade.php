@if ($plans->count() > 0)
    <table class="table table-box pml-table"
        current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
    >
        @foreach ($plans as $key => $plan)
            <tr>
                <td width="1%">
                    <div class="text-nowrap">
                        <div class="checkbox inline">
                            <input type="hidden" class="node styled"
                                custom-order="{{ $plan->custom_order }}"
                                name="ids[]"
                                value="{{ $plan->uid }}"
                            />
                        </div>
                        @if (request()->sort_order == 'custom_order' && empty(request()->keyword))
                            <i data-action="move" class="icon icon-more2 list-drag-button"></i>
                        @endif
                    </div>
                </td>
                <td>
                    <h5 class="no-margin text-bold">
                        <span class="kq_search" href="{{ action('Admin\PlanController@edit', $plan->uid) }}">
                            {{ $plan->name }}
                        </span>
                    </h5>
                    @if (!$plan->useSystemSendingServer())
                        {{ trans('messages.plan.sending_server.' . $plan->getOption('sending_server_option')) }}  &bull; {{ trans('messages.plans.subscriber_count', ['count' => $plan->customersCount()]) }}
                    @elseif ($plan->hasPrimarySendingServer())
                        <span class="text-muted">{{ trans('messages.plans.send_via.wording', ['server' => trans('messages.' . $plan->primarySendingServer()->type)]) }} &bull; {{ trans('messages.plans.subscriber_count', ['count' => $plan->customersCount()]) }}</span>
                    @else
                        <span class="text-muted"><span class="text-danger"><i class="fa fa-minus-circle"></i> {{ trans('messages.plans.send_via.empty') }}</span></span>
                    @endif
                </td>
                <td>
                    <h5 class="no-margin text-bold kq_search">
                        {{ \Acelle\Library\Tool::format_price($plan->price, $plan->currency->format) }}
                    </h5>
                    <span class="text-muted">{{ $plan->displayFrequencyTime() }}</span>
                </td>
                <td>
                    <h5 class="no-margin text-bold kq_search">
                        {{ $plan->displayTotalQuota() }}
                    </h5>
                    <span class="text-muted">{{ trans('messages.sending_total_quota_label') }}</span>
                </td>
                <td>
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-{{ $plan->status }}">{{ trans('messages.plan_status_' . $plan->status) }}</span>
                    </span>
                </td>
                <td class="text-right text-nowrap" width="5%">
                    @can('update', $plan)
                        <a href="{{ action('Admin\PlanController@general', $plan->uid) }}" type="button" class="btn bg-grey btn-icon"> <i class="icon-pencil"></i> {{ trans('messages.edit') }}</a>
                    @endcan
                    @if (\Auth::user()->can('delete', $plan) || \Auth::user()->can('disable', $plan) || \Auth::user()->can('enable', $plan) || \Auth::user()->can('copy', $plan))
                        <div class="btn-group">
                            <button type="button" class="btn dropdown-toggle" data-toggle="dropdown"><span class="caret ml-0"></span></button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                @if (Auth::user()->admin->can('enable', $plan))
                                    <li>
                                        <a link-confirm="{{ trans('messages.enable_plans_confirm') }}" href="{{ action('Admin\PlanController@enable', ["uids" => $plan->uid]) }}">
                                            <i class="icon-checkbox-checked2"></i> {{ trans('messages.enable') }}
                                        </a>
                                    </li>
                                @endif
                                @if (Auth::user()->admin->can('disable', $plan))
                                    <li>
                                        <a link-confirm="{{ trans('messages.disable_plans_confirm') }}" href="{{ action('Admin\PlanController@disable', ["uids" => $plan->uid]) }}">
                                            <i class="icon-checkbox-unchecked2"></i> {{ trans('messages.disable') }}
                                        </a>
                                    </li>
                                @endif
                                 @can('copy', $plan)
                                    <li>
                                        <a data-name="{{'Copy of '}}{{$plan->name}}" data-uid="{{$plan->uid}}" title="{{ trans('messages.copy') }}" class="copy-plan-link">
                                            <i class="icon icon-copy4"></i> {{ trans('messages.copy') }}
                                        </a>
                                    </li>
                                  @endcan
                                @can('delete', $plan)
                                    <li>
                                        <a list-delete-confirm="{{ action('Admin\PlanController@deleteConfirm', ['uids' => $plan->uid]) }}" href="{{ action('Admin\PlanController@delete', ['uids' => $plan->uid]) }}" title="{{ trans('messages.delete') }}" class="">
                                            <i class="icon icon-trash"></i> {{ trans('messages.delete') }}
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    @include('elements/_per_page_select', ["items" => $plans])
    {{ $plans->links() }}
@elseif (!empty(request()->keyword))
    <div class="empty-list">
        <i class="icon-clipboard2"></i>
        <span class="line-1">
            {{ trans('messages.no_search_result') }}
        </span>
    </div>
@else
    <div class="empty-list">
        <i class="icon-clipboard2"></i>
        <span class="line-1">
            {{ trans('messages.plan_empty_line_1') }}
        </span>
    </div>
@endif
