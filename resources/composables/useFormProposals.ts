import { toFormValues } from '@/lib/portfolioForm'
import type { AssistantProposal } from '@/types/assistant'
import type { PortfolioField, PortfolioFieldValue } from '@/types/portfolio'
import { computed, ref } from 'vue'

/**
 * Load the assistant's proposed values into a portfolio form without saving them, and put back what each one replaced.
 */
export const useFormProposals = (form: Record<string, PortfolioFieldValue>, fields: () => PortfolioField[]) => {
    const replaced = ref<Record<number, Record<string, PortfolioFieldValue>>>({})
    const loadedProposalIds = computed(() => Object.keys(replaced.value).map(Number))

    const load = (proposal: AssistantProposal): PortfolioField[] => {
        const names = proposal.fields.map(({ name }) => name)
        const changed = fields().filter(({ name }) => names.includes(name))

        replaced.value = {
            ...replaced.value,
            [proposal.id]: Object.fromEntries(
                changed.map(({ name }) => [name, JSON.parse(JSON.stringify(form[name] ?? null)) as PortfolioFieldValue]),
            ),
        }

        Object.assign(form, toFormValues(changed, Object.fromEntries(proposal.fields.map(({ name, after }) => [name, after]))))

        return changed
    }

    const unload = (proposal: AssistantProposal) => {
        const { [proposal.id]: before, ...rest } = replaced.value

        if (before) {
            Object.assign(form, before)
            replaced.value = rest
        }
    }

    const forget = () => (replaced.value = {})

    return { loadedProposalIds, load, unload, forget }
}
