import { useWorkspace, WorkspaceLayout, Pagination, StatusBadge, EmptyState, type Props } from '@/components/ai-pro/admin/workspace';

export default function Conversations(props: Props) {
  const workspace = useWorkspace(props, 'conversations');
  const { site, data, conversation, messages, messagePage, selectConversation, setMessagePage, setPage } = workspace;
  return <WorkspaceLayout props={props} section="conversations" workspace={workspace}>{site ? <div className="ai-pro-conversation-grid">
    <section className="workspace-card ai-pro-card"><h2>Recent conversations</h2><p>Chats expire after 30 days. Select a conversation to review its messages.</p>
      <ul className="ai-pro-list ai-pro-conversation-list">{data.conversations?.data.map(item => <li key={item.id}><button type="button" className="ai-pro-button" aria-pressed={Number(conversation) === item.id} onClick={() => selectConversation(item.id)}><span>Conversation #{item.id}</span><time dateTime={item.created_at}>{new Date(item.created_at).toLocaleString()}</time></button></li>)}</ul>
      {data.conversations?.total === 0 && <EmptyState>No conversations yet. Visitor chats will appear here.</EmptyState>}
      <Pagination page={data.conversations} change={setPage} label="conversations" />
    </section>
    <section className="workspace-card ai-pro-card"><h2>{conversation ? `Conversation #${conversation}` : 'Conversation details'}</h2>
      {!conversation ? <EmptyState>Select a conversation to view questions, answers, and their status.</EmptyState> : messages ? <div aria-label="Conversation messages">
        {messages.data.length === 0 && <EmptyState>This conversation has no messages yet.</EmptyState>}
        {messages.data.map(item => <article key={item.id} className="ai-pro-message"><div className="ai-pro-message-label">Visitor</div><div className="ai-pro-message-question">{item.question}</div><div className="ai-pro-message-label">iTechOrbit AI</div><div className="ai-pro-message-answer">{item.answer || 'No answer returned.'}</div><div className="ai-pro-message-meta"><StatusBadge status={item.status} /><time dateTime={item.created_at}>{new Date(item.created_at).toLocaleString()}</time></div></article>)}
        {messages.last_page > 1 && <div className="ai-pro-pagination"><button className="ai-pro-button" disabled={messagePage <= 1} onClick={() => setMessagePage(p => p - 1)}>Newer messages</button><span>Page {messagePage} of {messages.last_page}</span><button className="ai-pro-button" disabled={messagePage >= messages.last_page} onClick={() => setMessagePage(p => p + 1)}>Older messages</button></div>}
      </div> : <p role="status">Loading conversation…</p>}
    </section>
  </div> : <EmptyState>Add a website to view its conversations.</EmptyState>}</WorkspaceLayout>;
}
