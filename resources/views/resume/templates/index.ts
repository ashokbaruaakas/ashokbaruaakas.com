import type { Component } from 'vue'
import Classic from './Classic.vue'
import Compact from './Compact.vue'

export const resumeTemplates: Record<string, Component> = { classic: Classic, compact: Compact }

export const TEMPLATE_ESTIMATE_MESSAGE = 'resume-template-estimate'

export const resolveResumeTemplate = (template: string): Component => resumeTemplates[template] ?? Classic
