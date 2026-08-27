<style>
    .module-action-grid {
        display: grid;
        gap: 0.65rem;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }

    .module-action-btn {
        align-items: center;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        justify-content: center;
        min-height: 74px;
        white-space: normal;
    }

    .module-action-btn i {
        font-size: 1.35rem;
        line-height: 1;
    }

    .module-action-btn span {
        display: block;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .portfolio-state-grid {
        display: grid;
        gap: 0.65rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .portfolio-state-card {
        align-items: center;
        background: #fff;
        border: 1px solid var(--state-border, #cbd5e1);
        border-radius: 12px;
        color: #0f172a;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 112px;
        padding: 0.75rem 0.45rem;
        position: relative;
        text-align: center;
        text-decoration: none;
        transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    .portfolio-state-card:hover {
        background: var(--state-soft, #f8fafc);
        box-shadow: 0 9px 18px rgba(15, 23, 42, .10);
        color: #0f172a;
        text-decoration: none;
        transform: translateY(-2px);
    }

    .portfolio-state-card i { color: var(--state-color, #475569); font-size: 1.55rem; line-height: 1; margin: .25rem 0; }
    .portfolio-state-card strong { font-size: .82rem; line-height: 1.15; }
    .portfolio-state-card small { color: #64748b; font-size: .66rem; line-height: 1.15; margin-top: .2rem; }
    .portfolio-state-code { color: var(--state-color, #475569); font-size: .61rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .state-pending { --state-border: #f59e0b; --state-color: #b45309; --state-soft: #fffbeb; }
    .state-approved { --state-border: #0ea5e9; --state-color: #0369a1; --state-soft: #f0f9ff; }
    .state-active { --state-border: #22c55e; --state-color: #15803d; --state-soft: #f0fdf4; }
    .state-closed { --state-border: #94a3b8; --state-color: #475569; --state-soft: #f8fafc; }
    .state-rejected { --state-border: #ef4444; --state-color: #b91c1c; --state-soft: #fef2f2; }
    .state-restructured { --state-border: #8b5cf6; --state-color: #6d28d9; --state-soft: #f5f3ff; }
    .state-stopped { --state-border: #334155; --state-color: #0f172a; --state-soft: #f1f5f9; }
    .state-overdue { --state-border: #f97316; --state-color: #c2410c; --state-soft: #fff7ed; }

    .portfolio-analysis-grid {
        display: grid;
        gap: 0.65rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    @media (max-width: 1199.98px) {
        .portfolio-state-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 767.98px) {
        .portfolio-state-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
</style>
