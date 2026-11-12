import type { Component } from 'vue'
import Classic from './Classic.vue'
import Compact from './Compact.vue'
import Sidebar from './Sidebar.vue'

export type ResumeDesign = { template: string; accent: string; accentColor: string; font: string }

export const resumeTemplates: Record<string, Component> = { classic: Classic, compact: Compact, sidebar: Sidebar }

export const resumeFonts: Record<string, { body: string; name: string }> = {
    arial: { body: 'Arial, Helvetica, sans-serif', name: 'Arial, Helvetica, sans-serif' },
    poppins: { body: 'Poppins, Arial, Helvetica, sans-serif', name: "'Playfair Display', Georgia, serif" },
    instrument: { body: "'Instrument Sans', Arial, Helvetica, sans-serif", name: "'Instrument Sans', Arial, Helvetica, sans-serif" },
}

export const resolveResumeTemplate = (template: string): Component => resumeTemplates[template] ?? Classic
