/**
 * Modx3ProfilerToolbar frontend toolbar (Vue Petite).
 * Expects window.modx3profilertoolbarToolbarData = { metrics, info, slow_components, timeline, sql, lexicon }
 */
import { createApp } from 'https://unpkg.com/petite-vue@0.4.1/dist/petite-vue.es.js?module'

const TOOLBAR_ID = 'modx3profilertoolbar-toolbar'

function initToolbar(data) {
  if (document.getElementById(TOOLBAR_ID)) return

  const container = document.createElement('div')
  container.id = TOOLBAR_ID
  container.className = 'mpr-toolbar'

  const metrics = data.metrics || {}
  const info = data.info || {}
  const slowComponents = data.slow_components || []
  const timeline = data.timeline || []
  const sqlQueries = data.sql || []
  const lex = data.lexicon || {}
  const advisor = data.advisor || []
  const performanceScore = data.performance_score ?? null

  const scope = {
    metrics,
    info,
    slowComponents,
    timeline,
    sqlQueries,
    lex,
    advisor,
    performanceScore,
    expanded: false,
    activeTab: 'overview',
    tabs: [
      { id: 'overview', label: lex.overview || 'Overview' },
      { id: 'advisor', label: lex.advisor || 'Advisor' },
      { id: 'slow', label: lex.slow_components || 'Slow Components' },
      { id: 'timeline', label: lex.timeline || 'Timeline' },
      { id: 'sql', label: lex.sql || 'SQL' },
    ],
    toggle() {
      this.expanded = !this.expanded
    },
    formatMemory(bytes) {
      if (bytes == null) return '—'
      return (bytes / 1024 / 1024).toFixed(2) + ' MB'
    },
    getTimeClass(ms) {
      if (ms == null) return ''
      if (ms < 200) return 'mpr-metric--fast'
      if (ms < 500) return 'mpr-metric--warning'
      return 'mpr-metric--slow'
    },
    getScoreClass(score) {
      if (score == null) return ''
      if (score >= 80) return 'mpr-score--excellent'
      if (score >= 50) return 'mpr-score--fair'
      return 'mpr-score--critical'
    },
    getScoreLabel(score) {
      if (score == null) return lex.no_data || 'No data'
      if (score >= 80) return lex.status_excellent || 'Excellent'
      if (score >= 50) return lex.status_fair || 'Fair'
      return lex.status_critical || 'Critical'
    },
    getBarStyle(event) {
      if (!this.timeline.length) return {}
      const maxDuration = Math.max(...this.timeline.map((e) => e.duration || 0), 1)
      const width = ((event.duration || 0) / maxDuration) * 100
      return { width: width + '%' }
    },
  }

  container.innerHTML = `
    <div class="mpr-toolbar__inner" v-scope="toolbarScope">
      <div class="mpr-toolbar__compact" v-if="!expanded" @click="toggle" title="Click to expand">
        <span class="mpr-toolbar__icon">⏱</span>
        <span class="mpr-toolbar__value">{{ metrics.total_time }} ms</span>
        <span class="mpr-toolbar__sep"></span>
        <span class="mpr-toolbar__icon">🧠</span>
        <span class="mpr-toolbar__value">{{ formatMemory(metrics.memory_peak) }}</span>
        <span class="mpr-toolbar__sep"></span>
        <span class="mpr-toolbar__icon">🧩</span>
        <span class="mpr-toolbar__value">{{ metrics.snippet_count }} {{ lex.snippets || 'snip' }}</span>
        <span class="mpr-toolbar__sep"></span>
        <span class="mpr-toolbar__icon">🛢</span>
        <span class="mpr-toolbar__value">{{ metrics.sql_count }} {{ lex.sql_queries || 'SQL' }}</span>
      </div>

      <div class="mpr-toolbar__expanded" v-if="expanded">
        <div class="mpr-toolbar__header">
          <h3>{{ lex.performance_profiler || 'Performance Profiler' }}</h3>
          <button @click="toggle" class="mpr-toolbar__close">×</button>
        </div>

        <div class="mpr-toolbar__tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="['mpr-toolbar__tab', activeTab === tab.id && 'mpr-toolbar__tab--active']"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="mpr-toolbar__content">
          <div v-if="activeTab === 'overview'" class="mpr-tab-overview">
            <div class="mpr-metric mpr-metric--score" v-if="performanceScore !== null">
              <span class="mpr-metric__label">{{ lex.performance_score || 'Performance Score' }}:</span>
              <span class="mpr-metric__value" :class="getScoreClass(performanceScore)">
                <span class="mpr-metric__dot" :class="getScoreClass(performanceScore)"></span>
                {{ performanceScore }} / 100 ({{ getScoreLabel(performanceScore) }})
              </span>
            </div>
            <div class="mpr-metric">
              <span class="mpr-metric__label">{{ lex.total_time || 'Total time' }}:</span>
              <span class="mpr-metric__value" :class="getTimeClass(metrics.total_time)"><span class="mpr-metric__dot" :class="getTimeClass(metrics.total_time)"></span>{{ metrics.total_time }} ms</span>
            </div>
            <div class="mpr-metric">
              <span class="mpr-metric__label">{{ lex.ttfb || 'TTFB' }}:</span>
              <span class="mpr-metric__value" :class="getTimeClass(metrics.ttfb)"><span class="mpr-metric__dot" :class="getTimeClass(metrics.ttfb)"></span>{{ metrics.ttfb }} ms</span>
            </div>
            <div class="mpr-metric">
              <span class="mpr-metric__label">{{ lex.php || 'PHP' }}:</span>
              <span class="mpr-metric__value" :class="getTimeClass(metrics.php_time)"><span class="mpr-metric__dot" :class="getTimeClass(metrics.php_time)"></span>{{ metrics.php_time }} ms</span>
            </div>
            <div class="mpr-metric">
              <span class="mpr-metric__label">{{ lex.sql_time || 'SQL' }}:</span>
              <span class="mpr-metric__value" :class="getTimeClass(metrics.sql_time)"><span class="mpr-metric__dot" :class="getTimeClass(metrics.sql_time)"></span>{{ metrics.sql_time }} ms</span>
            </div>
            <div class="mpr-info-block">
              <div class="mpr-info-line">{{ lex.query_time || 'Query time' }}: {{ info.queryTime || '—' }}</div>
              <div class="mpr-info-line">{{ lex.source || 'Source' }}: {{ info.source || '—' }}</div>
            </div>
          </div>

          <div v-if="activeTab === 'advisor'" class="mpr-tab-advisor">
            <div v-if="advisor.length === 0" class="mpr-advisor-empty">{{ lex.no_data || 'No recommendations' }}</div>
            <div v-for="(item, i) in advisor" :key="i" class="mpr-advisor-item">
              <div class="mpr-advisor-message">{{ item.message }}</div>
              <div v-if="item.suggestion" class="mpr-advisor-suggestion">💡 {{ item.suggestion }}</div>
            </div>
          </div>

          <div v-if="activeTab === 'slow'" class="mpr-tab-slow">
            <div v-for="(comp, i) in slowComponents" :key="i" class="mpr-component mpr-component--clickable">
              <div class="mpr-component__line">
                {{ comp.type }}: {{ comp.name }} — {{ comp.time }} ms
                <span v-if="comp.calls != null">({{ comp.calls }} {{ lex.calls || 'calls' }})</span>
                <span v-if="comp.cached !== undefined && comp.cached !== null"> — {{ lex.cached || 'cached' }}: {{ comp.cached ? (lex.yes || 'yes') : (lex.no || 'no') }}</span>
              </div>
            </div>
          </div>

          <div v-if="activeTab === 'timeline'" class="mpr-tab-timeline">
            <div v-for="(event, i) in timeline" :key="i" class="mpr-timeline-item">
              <div class="mpr-timeline-bar" :style="getBarStyle(event)"></div>
              <div class="mpr-timeline-label">{{ event.name }}</div>
              <div class="mpr-timeline-time">{{ event.duration }} ms</div>
            </div>
          </div>

          <div v-if="activeTab === 'sql'" class="mpr-tab-sql">
            <div v-for="(query, idx) in sqlQueries" :key="idx" class="mpr-sql-query">
              <div class="mpr-sql-text">{{ query.text }}</div>
              <div class="mpr-sql-meta">
                <span>{{ lex.time || 'Time' }}: {{ query.time }} ms</span>
                <span>{{ lex.calls || 'Calls' }}: {{ query.count }}</span>
                <span v-if="query.is_n1" class="mpr-sql-warning">⚠️ {{ lex.possible_n1 || 'Possible N+1' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `

  document.body.appendChild(container)

  createApp({ toolbarScope: scope }).mount('#' + TOOLBAR_ID)
}

if (typeof window !== 'undefined' && window.modx3profilertoolbarToolbarData) {
  initToolbar(window.modx3profilertoolbarToolbarData)
}

if (typeof window !== 'undefined') {
  window.modx3profilertoolbarToolbar = { init: initToolbar }
}
