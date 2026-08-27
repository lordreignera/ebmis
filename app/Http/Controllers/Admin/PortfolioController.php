<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GroupLoan;
use App\Models\PersonalLoan;
use App\Models\Product;
use App\Services\LoanAccessService;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(private LoanAccessService $loanAccessService)
    {
    }

    public function running(Request $request)
    {
        $request->attributes->set('portfolio_title', 'Active / Disbursed Loans');

        return app(RepaymentController::class)->activeLoans($request);
    }

    public function pending(Request $request)
    {
        return $this->loanRegister($request, 0, 'Pending Loan Applications');
    }

    public function approved(Request $request)
    {
        return $this->loanRegister($request, 1, 'Approved — Awaiting Disbursement');
    }

    public function overdue(Request $request)
    {
        $request->merge(['status' => 'overdue']);
        $request->attributes->set('portfolio_title', 'Overdue Active Loans');

        return app(RepaymentController::class)->activeLoans($request);
    }

    public function paid(Request $request)
    {
        return $this->loanRegister($request, 3, 'Closed Loans');
    }

    /**
     * Keep the legacy URL working, but map it to the real stopped-loan state.
     */
    public function bad(Request $request)
    {
        return $this->stopped($request);
    }

    public function rejected(Request $request)
    {
        return $this->loanRegister($request, 4, 'Rejected Loans');
    }

    public function restructured(Request $request)
    {
        // Status 5 belongs to the original loan that was replaced. The usable
        // restructured facility is a separate R-prefixed loan record and keeps
        // its operational status (normally status 2 while it is being repaid).
        $request->attributes->set('portfolio_restructured_replacements', true);
        $request->attributes->set('portfolio_title', 'Restructured Loans');

        return app(LoanController::class)->index($request);
    }

    public function stopped(Request $request)
    {
        return $this->loanRegister($request, 6, 'Stopped Loans');
    }

    public function branch()
    {
        $items = $this->loanAccessService->branchesForUser(Branch::active())
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch) => $this->dimensionRow(
                $branch->name,
                'Branch',
                ['branch_id' => $branch->id],
                $this->loanAccessService->scopeLoanQuery(PersonalLoan::where('branch_id', $branch->id)),
                $this->loanAccessService->scopeLoanQuery(GroupLoan::where('branch_id', $branch->id))
            ));

        return view('admin.portfolio.dimension', [
            'pageTitle' => 'Portfolio by Branch',
            'pageSubtitle' => 'Loan value and lifecycle distribution for every accessible branch.',
            'dimensionLabel' => 'Branch',
            'items' => $items,
        ]);
    }

    public function product()
    {
        $items = Product::loanProducts()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => $this->dimensionRow(
                $product->name,
                'Product',
                ['product_id' => $product->id],
                $this->loanAccessService->scopeLoanQuery(PersonalLoan::where('product_type', $product->id)),
                $this->loanAccessService->scopeLoanQuery(GroupLoan::where('product_type', $product->id))
            ));

        return view('admin.portfolio.dimension', [
            'pageTitle' => 'Portfolio by Product',
            'pageSubtitle' => 'Loan value and lifecycle distribution for every active loan product.',
            'dimensionLabel' => 'Product',
            'items' => $items,
        ]);
    }

    public function group(Request $request)
    {
        $request->merge(['type' => 'group']);
        $request->attributes->set('portfolio_title', 'Group Loan Portfolio');

        return app(LoanController::class)->index($request);
    }

    private function loanRegister(Request $request, int $status, string $title)
    {
        $request->merge(['status' => (string) $status]);
        $request->attributes->set('portfolio_title', $title);

        return app(LoanController::class)->index($request);
    }

    private function dimensionRow($name, string $type, array $filters, $personalQuery, $groupQuery): array
    {
        $statusCounts = [];

        foreach (range(0, 6) as $status) {
            $statusCounts[$status] = (clone $personalQuery)->where('status', $status)->count()
                + (clone $groupQuery)->where('status', $status)->count();
        }

        return [
            'name' => $name,
            'type' => $type,
            'filters' => $filters,
            'total' => (clone $personalQuery)->count() + (clone $groupQuery)->count(),
            'principal' => (float) (clone $personalQuery)->sum('principal')
                + (float) (clone $groupQuery)->sum('principal'),
            'statuses' => $statusCounts,
        ];
    }
}
