<style>
    * { box-sizing: border-box; }
    body { margin: 0; background: #f3f7fa; color: #193344; font: 16px/1.65 system-ui, sans-serif; }
    .report-shell { max-width: 1120px; margin: auto; padding: 24px; }
    .report-toolbar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 0; background: #f3f7fa; border-bottom: 1px solid #d8e5eb; margin-bottom: 24px; }
    .report-button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 16px; border: 1px solid #b8ced7; border-radius: 8px; color: #193344; background: white; font: inherit; font-size: .9rem; font-weight: 600; text-decoration: none; cursor: pointer; }
    .report-button:hover { background: #e0f2f1; }
    .report-button:focus-visible { outline: 3px solid #087e79; outline-offset: 3px; }
    .report-button-primary { background: #086b67; color: white; border-color: #086b67; margin-left: auto; }
    .report-button-primary:hover { background: #075753; }
    .report-header { padding: 28px; border: 1px solid #d8e5eb; border-radius: 16px; background: white; margin-bottom: 24px; }
    .report-eyebrow { color: #087e79; font-size: .8rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; }
    h1 { font-size: clamp(1.6rem, 4vw, 2.25rem); line-height: 1.2; margin: 8px 0 14px; }
    h2, h3, h4 { line-height: 1.35; overflow-wrap: anywhere; }
    p, li, dd { overflow-wrap: anywhere; }
    a { color: #1257a0; }
    .report-muted, .text-muted, small { color: #526873; }
    .report-panel { background: white; border: 1px solid #d8e5eb; border-radius: 14px; padding: 24px; margin-bottom: 20px; }
    .report-plan { white-space: pre-wrap; overflow-wrap: anywhere; }
    .site-report .age-results { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin: 24px 0; }
    .age-result-card { padding: 20px; border-radius: 12px; background: #e0f2f1; height: 100%; }
    .age-result-card--amber { background: #fff1d6; }
    .age-result-card--blue { background: #e5eeff; }
    .age-result-card--purple { background: #efe8fa; }
    .age-result-value { font-size: 2rem; font-weight: 700; margin: 0; }
    .age-result-card p:last-child { margin: 0; font-size: .9rem; }
    dt { font-weight: 700; }
    dd { margin: 0 0 12px; }
    li { margin-bottom: 8px; }
    .report-notice { background: #fff1d6; border-left: 4px solid #ba790c; border-radius: 6px; padding: 12px 16px; }
    @media (max-width: 650px) { .report-shell { padding: 14px; } .site-report .age-results { grid-template-columns: repeat(2, minmax(0, 1fr)); } .report-header, .report-panel { padding: 20px; } .report-toolbar { position: static; } .report-button-primary { margin-left: 0; } }
    @media print { body { background: white; font-size: 10pt; } .report-shell { max-width: none; padding: 0; } .report-toolbar, .report-notice { display: none; } .report-header, .report-panel { border: 0; padding: 0; } h2, h3, h4 { break-after: avoid; } .site-report .age-results { grid-template-columns: repeat(4, 1fr); } .report-panel { margin-bottom: 24px; } }
</style>
