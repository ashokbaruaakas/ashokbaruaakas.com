import { onMounted, ref } from 'vue'

export const useIsSignedIn = () => {
    const isSignedIn = ref(false)

    onMounted(() => {
        isSignedIn.value = document.cookie.split('; ').includes('signed_in=1')
    })

    return isSignedIn
}
