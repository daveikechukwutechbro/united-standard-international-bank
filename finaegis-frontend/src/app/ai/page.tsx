"use client"

import { useState, useRef, useEffect } from "react"
import { useMutation } from "@apollo/client"
import { SEND_AI_MESSAGE } from "@/lib/graphql/mutations"
import { AppShell } from "@/components/layout/AppShell"
import { Bot, Send, User } from "lucide-react"

interface Message {
  role: "user" | "assistant"
  content: string
}

function AiContent() {
  const [conversationId, setConversationId] = useState<string | null>(null)
  const [messages, setMessages] = useState<Message[]>([])
  const [input, setInput] = useState("")
  const [sendAiMessage, { loading }] = useMutation(SEND_AI_MESSAGE)
  const bottomRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" })
  }, [messages])

  const handleSend = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!input.trim() || loading) return

    const userMsg: Message = { role: "user", content: input }
    setMessages((prev) => [...prev, userMsg])
    setInput("")

    const { data } = await sendAiMessage({
      variables: {
        input: {
          message: input,
          conversation_id: conversationId,
        },
      },
    })

    if (data?.sendAiMessage) {
      const { conversation_id, response } = data.sendAiMessage
      setConversationId(conversation_id)
      setMessages((prev) => [...prev, { role: "assistant", content: response }])
    }
  }

  return (
    <div className="flex h-full flex-col">
      <div>
        <h1 className="text-2xl font-semibold text-white mb-1">AI Assistant</h1>
        <p className="text-sm text-neutral-400 mb-6">Chat with FinAegis AI</p>
      </div>

      <div className="flex flex-1 flex-col rounded-xl border border-neutral-800 bg-neutral-900 overflow-hidden">
        <div className="flex-1 overflow-y-auto p-4 space-y-4">
          {messages.length === 0 && (
            <div className="flex h-full items-center justify-center">
              <div className="text-center">
                <Bot size={40} className="mx-auto text-neutral-600 mb-3" />
                <p className="text-sm text-neutral-500">Send a message to start chatting</p>
              </div>
            </div>
          )}
          {messages.map((msg, i) => (
            <div key={i} className={`flex max-w-[80%] ${msg.role === "user" ? "ml-auto" : "mr-auto"}`}>
              <div className="flex items-start gap-2">
                <div className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${msg.role === "user" ? "bg-blue-600" : "bg-neutral-700"}`}>
                  {msg.role === "user" ? <User size={13} /> : <Bot size={13} />}
                </div>
                <div className={`rounded-2xl px-4 py-2.5 text-sm ${msg.role === "user" ? "bg-blue-600 text-white" : "bg-neutral-800 text-neutral-200"}`}>
                  {msg.content}
                </div>
              </div>
            </div>
          ))}
          <div ref={bottomRef} />
        </div>

        <form onSubmit={handleSend} className="border-t border-neutral-800 p-4">
          <div className="flex gap-3">
            <input
              type="text"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              placeholder="Type your message..."
              className="flex-1 rounded-lg border border-neutral-700 bg-neutral-800 px-4 py-2.5 text-sm text-white placeholder-neutral-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <button
              type="submit"
              disabled={loading || !input.trim()}
              className="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
            >
              <Send size={16} />
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default function AiPage() {
  return (
    <AppShell>
      <AiContent />
    </AppShell>
  )
}
