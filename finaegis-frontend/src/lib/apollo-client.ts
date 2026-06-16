"use client"

import { ApolloClient, InMemoryCache, createHttpLink, split } from "@apollo/client"
import { setContext } from "@apollo/client/link/context"
import { GraphQLWsLink } from "@apollo/client/link/subscriptions"
import { createClient } from "graphql-ws"
import { getMainDefinition } from "@apollo/client/utilities"

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000"
const WS_URL = process.env.NEXT_PUBLIC_WS_URL || "ws://localhost:8000"

const httpLink = createHttpLink({
  uri: `${API_URL}/graphql`,
})

function getAuthToken() {
  if (typeof window === "undefined") return null
  return localStorage.getItem("auth_token")
}

const authLink = setContext((_, { headers }) => {
  const token = getAuthToken()
  return {
    headers: {
      ...headers,
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
  }
})

let wsLink: GraphQLWsLink | null = null

if (typeof window !== "undefined") {
  wsLink = new GraphQLWsLink(
    createClient({
      url: `${WS_URL}/graphql`,
      connectionParams: () => {
        const token = getAuthToken()
        return token ? { Authorization: `Bearer ${token}` } : {}
      },
    })
  )
}

const splitLink = wsLink
  ? split(
      ({ query }) => {
        const definition = getMainDefinition(query)
        return definition.kind === "OperationDefinition" && definition.operation === "subscription"
      },
      wsLink,
      authLink.concat(httpLink)
    )
  : authLink.concat(httpLink)

export const client = new ApolloClient({
  link: splitLink,
  cache: new InMemoryCache({
    typePolicies: {
      Query: {
        fields: {
          accounts: {
            keyArgs: false,
            merge(existing = { data: [] }, incoming: any) {
              if (!incoming.data) return incoming
              if (!existing.data) return incoming
              return {
                ...incoming,
                data: [...existing.data, ...incoming.data],
              }
            },
          },
          payments: {
            keyArgs: false,
            merge(existing = { data: [] }, incoming: any) {
              if (!incoming.data) return incoming
              if (!existing.data) return incoming
              return {
                ...incoming,
                data: [...existing.data, ...incoming.data],
              }
            },
          },
        },
      },
    },
  }),
  defaultOptions: {
    watchQuery: {
      fetchPolicy: "cache-and-network",
    },
  },
})
